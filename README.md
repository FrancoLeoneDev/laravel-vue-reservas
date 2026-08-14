# Nova Studio — Sistema de reservas de turnos

Aplicación de reservas de turnos para un negocio de servicios (una peluquería, en este
caso). Los clientes eligen un servicio, ven la disponibilidad **real** y reservan; el
negocio gestiona su agenda, sus servicios y sus horarios desde un panel de administración.

El foco del proyecto no es el CRUD: es que **dos personas no puedan quedarse con el mismo
turno**. Esa parte está explicada en detalle más abajo, en
[El problema de la doble reserva](#el-problema-de-la-doble-reserva).

---

## Demo

**URL:** _(pendiente de deploy — se completa al publicar)_

Hay dos cuentas de demostración, visibles en la propia pantalla de login con un botón para
entrar de un clic:

| Rol           | Email              | Contraseña |
| ------------- | ------------------ | ---------- |
| Administrador | `admin@demo.com`   | `password` |
| Cliente       | `cliente@demo.com` | `password` |

Los datos son de ejemplo y se regeneran con los seeders.

---

## Stack

| Capa           | Tecnología                                                  |
| -------------- | ----------------------------------------------------------- |
| Backend        | PHP 8.3+ · Laravel 13                                       |
| Frontend       | Vue 3 (`<script setup>`, Composition API) · Inertia 3        |
| Estilos        | Tailwind CSS 4 · shadcn-vue                                  |
| Rutas tipadas  | Laravel Wayfinder                                            |
| Autenticación  | Laravel Fortify (del starter kit oficial de Vue + Inertia)   |
| Base de datos  | MySQL 8.4 (Docker Compose)                                   |
| Colas          | Driver `database` (email de confirmación en background)      |
| Tests          | Pest                                                         |

---

## Capturas

_(Se agregan al publicar el deploy.)_

---

## Alcance

**Público**

- Listado de servicios con duración, precio y horarios de atención.
- Selector de fecha y hora que muestra **sólo huecos realmente disponibles**.
- Reservar (requiere cuenta; mirar la disponibilidad, no).

**Cliente autenticado**

- "Mis reservas", separadas en próximos turnos e historial.
- Cancelar un turno propio (libera el horario para el resto).

**Administrador**

- Agenda del día y de la semana, con métricas del período.
- ABM de servicios (nombre, duración, precio, alta/baja).
- ABM de la disponibilidad horaria semanal.
- Confirmar, cancelar o completar cualquier reserva.

---

## El problema de la doble reserva

Esta es la parte interesante del proyecto.

### La versión ingenua, y por qué está rota

Lo primero que uno escribe es esto:

```php
// ❌ Tiene una condición de carrera.
public function book(User $user, Service $service, CarbonImmutable $startsAt): Booking
{
    // (1) Consulto si está libre...
    $ocupado = Booking::query()
        ->blocking()
        ->overlapping($startsAt, $startsAt->addMinutes($service->duration_minutes))
        ->exists();

    if ($ocupado) {
        throw new SlotUnavailableException();
    }

    // (2) ...y después inserto.
    return Booking::create([...]);
}
```

Se ve razonable y pasa todos los tests secuenciales. El problema es que entre **(1)** y
**(2)** hay unos milisegundos en los que nada protege el turno:

```
        Request A                          Request B
           │                                  │
  (1) ¿10:00 libre? → sí                      │
           │                        (1) ¿10:00 libre? → sí     ← todavía nadie insertó
  (2) INSERT 10:00 ✅                          │
           │                        (2) INSERT 10:00 ✅         ← duplicado
           ▼                                  ▼
              dos clientes en el mismo horario
```

Es el clásico **TOCTOU** (_time-of-check to time-of-use_). No se arregla validando "mejor",
ni moviendo la validación a un Form Request, ni agregando un `if` más: mientras la
comprobación y la escritura sean dos operaciones separadas **sin un candado en el medio**,
la ventana existe. Con poco tráfico casi nunca se dispara, y por eso suele llegar a
producción y aparecer justo el día que el negocio publica una promoción.

En el código está dejado explícitamente como contraejemplo comentado, en el docblock de
[`app/Services/BookingService.php`](app/Services/BookingService.php).

### La solución, en tres capas

#### 1. Los huecos se calculan, no se almacenan

No existe una tabla de slots precargados. La disponibilidad se deriva en cada consulta:

```
agenda del negocio (Availability)  −  reservas que ocupan  ⊗  duración del servicio
```

Eso vive en [`app/Services/AvailabilityService.php`](app/Services/AvailabilityService.php).
Se recorre cada tramo de atención del día en pasos de 15 minutos, se descarta todo lo que
se solape con una reserva existente o ya haya pasado, y lo que queda son los huecos.

La ventaja no es sólo de diseño: si los slots fueran filas precargadas, cambiar el horario
de atención o la duración de un servicio obligaría a regenerarlas, y dejaría huérfanos los
turnos ya vendidos. Calculándolos, cambiar la agenda es un `UPDATE` y nada más.

Un detalle que importa: la comprobación "¿este horario es válido y está libre?" **reutiliza
la misma función** que genera la grilla que ve el usuario. No hay dos implementaciones que
puedan divergir — lo que la UI muestra y lo que el backend acepta salen del mismo lugar.

#### 2. Transacción con bloqueo, revalidando adentro

El corazón está en `BookingService::book()`:

```php
DB::transaction(function () use (...) {
    // PASO 1 — bloquear
    $lockedBookings = Booking::query()
        ->blocking()
        ->whereBetween('starts_at', [$startsAt->startOfDay(), $startsAt->endOfDay()])
        ->orderBy('starts_at')
        ->lockForUpdate()          // ← SELECT ... FOR UPDATE
        ->get([...]);

    // PASO 2 — revalidar YA bloqueado, con esas mismas filas
    if (! $this->availability->isBookableSlot($service, $startsAt, $lockedBookings)) {
        throw new SlotUnavailableException;
    }

    // PASO 3 — insertar
    return Booking::create([...]);
}, attempts: 3);
```

Tres cosas que hacen que esto funcione:

- **`lockForUpdate()` sobre el rango del día.** La segunda transacción que llegue queda
  esperando en el candado en vez de leer datos obsoletos. Cuando entra, ya ve la reserva
  que hizo la primera.

- **Gap locks.** El caso realmente traicionero es "todavía no hay ninguna reserva ese día":
  ahí un `SELECT` normal devuelve cero filas y no hay nada que bloquear. InnoDB, en
  `REPEATABLE READ`, aplica _next-key locking_: además de las filas existentes bloquea los
  **huecos entre ellas**, así que un `INSERT` concurrente dentro de ese rango también queda
  esperando. El índice `(status, starts_at)` declarado en la migración es el que sostiene
  ese bloqueo — no es decorativo.

- **Se revalida con las filas ya bloqueadas**, que se le pasan a `AvailabilityService` en
  lugar de dejar que las vuelva a consultar. Decidir sobre el mismo estado que se tiene
  bloqueado es justamente lo que cierra la ventana. Lo que el navegador haya visto al
  pintar la grilla no cuenta: pudo haber pasado un minuto.

El `attempts: 3` cubre el caso en que InnoDB detecte un deadlock entre dos transacciones y
mate una: Laravel la reintenta desde cero.

#### 3. Restricción de unicidad en la base, como última línea

Aunque el bloqueo falle (un índice caído, alguien tocando la base a mano, un refactor
futuro que rompa la transacción), la base de datos tiene la última palabra:

```php
// database/migrations/..._create_bookings_table.php
$table->dateTime('slot_key')
    ->storedAs("CASE WHEN status = 'cancelled' THEN NULL ELSE starts_at END")
    ->nullable();

$table->unique('slot_key', 'bookings_active_slot_unique');
```

`slot_key` es una **columna generada** que vale `starts_at` mientras la reserva ocupa la
agenda, y `NULL` cuando fue cancelada. El truco está en que **MySQL no considera los `NULL`
al evaluar la unicidad**: pueden convivir diez reservas canceladas de las 10:00, pero sólo
puede existir una activa que arranque a las 10:00.

Como efecto secundario elegante, **cancelar libera el horario automáticamente**: basta con
cambiar el estado, MySQL recalcula la columna a `NULL` y el hueco vuelve a aparecer en la
grilla. Sin borrar nada y sin perder el histórico.

El `INSERT` que viole ese índice se captura como `UniqueConstraintViolationException` y se
traduce al mismo error legible que el resto del flujo:

```php
} catch (UniqueConstraintViolationException $e) {
    throw new SlotUnavailableException(previous: $e);
}
```

### Hasta dónde llega cada capa (y qué no cubre)

Vale ser preciso, porque las tres capas no protegen exactamente lo mismo:

| Escenario                                                             | Transacción + lock | Índice único |
| --------------------------------------------------------------------- | :----------------: | :----------: |
| Dos reservas que arrancan **a la misma hora**                          |         ✅         |      ✅      |
| Solapamiento **parcial** (10:00–10:45 contra 10:30–11:15)              |         ✅         |      ❌      |

El índice único cubre las colisiones de inicio exacto. El solapamiento parcial —posible
porque cada servicio dura distinto— lo resuelve la revalidación dentro de la transacción,
que compara intervalos completos, no sólo el instante de inicio.

¿Por qué no una restricción de exclusión sobre el rango, que lo cubriría todo en SQL?
Porque MySQL no tiene `EXCLUDE USING gist` como PostgreSQL. Se podría emular con una tabla
de ocupación (una fila por cada bloque de 15 minutos cubierto, con `UNIQUE` sobre el
bloque), y sería la única forma de que el solapamiento parcial fuera imposible por
definición a nivel de esquema. Es una complejidad que este proyecto no necesita: con un
único recurso a agendar, el bloqueo serializa las escrituras del día y el índice queda como
red de seguridad. Si el negocio pasara a tener varios profesionales en paralelo, ese sería
el momento de introducirla.

### El test que lo demuestra

```bash
php artisan test --filter=doble
```

Hay un test que simula dos reservas concurrentes sobre el mismo horario y verifica que
exactamente una sobreviva. Está en `tests/Feature/`.

---

## Otras decisiones que se ven en el código

- **Form Requests** para toda la validación de entrada (`app/Http/Requests/`), con mensajes
  en español. La validación de formato vive ahí; la de disponibilidad, a propósito, no.
- **Policies** (`app/Policies/BookingPolicy.php`): un cliente sólo ve y cancela sus propias
  reservas. El admin puede con todas. Además hay un middleware `admin` que corta el acceso
  al panel a nivel de ruta, antes de llegar a la Policy.
- **Cuidado con el N+1**: la agenda carga `service` y `user` con eager loading (una semana
  con 40 turnos dispararía 80 consultas extra sin eso), el listado de servicios del admin
  usa `withCount` con subquery, y `AvailabilityService` cachea la agenda semanal para no
  consultarla una vez por día al armar el selector de 14 días.
- **Migraciones versionadas** y **seeders** con servicios, disponibilidad y reservas
  realistas repartidas en la semana, incluyendo turnos pasados completados y algunos
  cancelados.
- **Email de confirmación en cola**: al reservar se despacha un job (`SendBookingConfirmation`)
  que manda el mail fuera de la request. Si el SMTP está caído, la reserva ya está
  confirmada en base y el job reintenta; el usuario no espera y no ve un 500.
- **Zona horaria** fijada en `America/Argentina/Buenos_Aires` y locale `es`, así las fechas
  se muestran en español sin trabajo extra.

---

## Correrlo local

Requisitos: PHP 8.3+, Composer, Node 20+, Docker.

```bash
git clone https://github.com/FrancoLeoneDev/laravel-vue-reservas.git
cd laravel-vue-reservas

cp .env.example .env

# MySQL en el puerto 3309 (contenedor mysql-reservas)
docker compose up -d

composer install
npm install

php artisan key:generate
php artisan migrate --seed

npm run dev
php artisan serve --port=8002
```

La app queda en <http://localhost:8002>.

> **Nota sobre los puertos.** El proyecto usa el **8002** para Laravel y el **3309** para
> MySQL en lugar de los puertos por defecto, para poder convivir con otros proyectos
> Laravel corriendo en la misma máquina. Se cambian en `.env` y `docker-compose.yml`.

Para que se manden los emails de confirmación hace falta un worker:

```bash
php artisan queue:work
```

### Tests

```bash
php artisan test
```

---

## Estructura relevante

```
app/
├── Enums/
│   ├── BookingStatus.php          # confirmada / cancelada / completada
│   └── UserRole.php               # admin / cliente
├── Exceptions/
│   └── SlotUnavailableException.php
├── Http/
│   ├── Controllers/               # público, cliente y Admin/
│   ├── Middleware/EnsureUserIsAdmin.php
│   └── Requests/                  # Form Requests
├── Jobs/SendBookingConfirmation.php
├── Models/                        # Service, Availability, Booking, User
├── Policies/BookingPolicy.php
└── Services/
    ├── AvailabilityService.php    # ← cálculo de huecos
    └── BookingService.php         # ← transacción + bloqueo

database/migrations/               # incluye la columna generada y el índice único
resources/js/pages/                # Vue 3 con <script setup>
```
