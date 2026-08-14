<x-mail::message>
# ¡Listo, {{ $cliente }}! Tu turno está confirmado

Te esperamos en **Nova Studio**. Estos son los datos de tu reserva:

<x-mail::table>
| &nbsp; | &nbsp; |
|:---------------|:---------------------------|
| **Servicio** | {{ $servicio }} |
| **Cuándo** | {{ $cuando }} |
| **Duración** | {{ $duracion }} |
| **Precio** | {{ $precio }} |
</x-mail::table>

<x-mail::panel>
Te pedimos llegar 5 minutos antes. Si necesitás cambiar el turno, cancelalo y volvé a reservar el horario que mejor te venga.
</x-mail::panel>

<x-mail::button :url="$misReservasUrl">
Ver mis reservas
</x-mail::button>

¿No vas a poder venir? Podés cancelar el turno cuando quieras desde la sección **Mis reservas**. Cancelar libera el horario para otra persona, así que te agradecemos avisarnos con tiempo.

¡Nos vemos!<br>
El equipo de {{ config('app.name') }}

<x-mail::subcopy>
Si el botón «Ver mis reservas» no funciona, copiá y pegá esta dirección en tu navegador:
[{{ $misReservasUrl }}]({{ $misReservasUrl }})
</x-mail::subcopy>
</x-mail::message>
