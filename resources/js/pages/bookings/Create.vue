<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    CalendarX,
    Clock,
    Lock,
    TriangleAlert,
    Wallet,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { home, login } from '@/routes';
import {
    create as bookingCreate,
    store as bookingStore,
} from '@/routes/bookings';
import type { DaySummary, Service, Slot } from '@/types/booking';

const props = defineProps<{
    service: Service;
    days: DaySummary[];
    selectedDate: string;
    slots: Slot[];
    stepMinutes: number;
}>();

const page = usePage();

const user = computed(() => page.props.auth?.user ?? null);

/**
 * El error de `starts_at` vuelve por `back()->withErrors()` cuando otra persona
 * se quedó con el hueco mientras este visitante lo miraba. Lo leemos de las
 * props compartidas porque el aviso vive fuera del <Form>, arriba de la grilla.
 */
const slotError = computed<string | null>(() => {
    const error = page.props.errors?.starts_at;

    return typeof error === 'string' ? error : null;
});

const NOTES_MAX_LENGTH = 500;

const notes = ref('');
const selectedStart = ref<string | null>(null);

const selectedSlot = computed<Slot | null>(
    () =>
        props.slots.find((slot) => slot.starts_at === selectedStart.value) ??
        null,
);

const selectedDay = computed<DaySummary | null>(
    () => props.days.find((day) => day.date === props.selectedDate) ?? null,
);

const totalSlots = computed(() =>
    props.days.reduce((total, day) => total + day.slots, 0),
);

/**
 * Cuando la grilla se recalcula (cambio de día, o vuelta atrás tras un choque de
 * turnos) la selección local puede quedar apuntando a un hueco que ya no existe.
 */
watch(
    () => props.slots,
    (slots) => {
        if (
            selectedStart.value !== null &&
            !slots.some((slot) => slot.starts_at === selectedStart.value)
        ) {
            selectedStart.value = null;
        }
    },
);

const priceFormatter = new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    maximumFractionDigits: 0,
});

function formatPrice(price: string): string {
    return priceFormatter.format(Number(price));
}

/** 45 → "45 min", 60 → "1 h", 90 → "1 h 30 min". */
function formatDuration(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    if (hours === 0) {
        return `${rest} min`;
    }

    return rest === 0 ? `${hours} h` : `${hours} h ${rest} min`;
}

/** 'Y-m-d H:i:s' → 'HH:mm'. */
function timeOf(datetime: string): string {
    return datetime.slice(11, 16);
}

function shortWeekday(weekday: string): string {
    return weekday.slice(0, 3);
}

/**
 * "Cerrado" y "Completo" no son lo mismo y conviene que se note: un día cerrado no
 * va a tener turnos nunca, uno lleno puede liberarse si alguien cancela.
 */
function slotsLabel(day: DaySummary): string {
    if (!day.is_open) {
        return 'Cerrado';
    }

    if (day.slots === 0) {
        return 'Completo';
    }

    return day.slots === 1 ? '1 lugar' : `${day.slots} lugares`;
}

/**
 * Cambiar de día es una recarga PARCIAL de Inertia: con `only` el servidor sólo
 * vuelve a serializar `slots` y `selectedDate` en vez de toda la página, y con
 * `preserveState`/`preserveScroll` el componente no se remonta ni salta la vista.
 * Es una navegación real (queda en la URL y en el historial), pero paga sólo por
 * lo que efectivamente cambia.
 */
function selectDay(day: DaySummary): void {
    if (day.slots === 0 || day.date === props.selectedDate) {
        return;
    }

    router.get(
        bookingCreate(
            { service: props.service.slug },
            { query: { date: day.date } },
        ).url,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: ['slots', 'selectedDate'],
        },
    );
}

function selectSlot(slot: Slot): void {
    selectedStart.value =
        selectedStart.value === slot.starts_at ? null : slot.starts_at;
}
</script>

<template>
    <Head :title="`Reservar ${service.name}`" />

    <div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
        <!-- Encabezado del servicio -->
        <div class="mb-8">
            <Button
                as-child
                variant="ghost"
                size="sm"
                class="mb-4 -ml-3 text-muted-foreground"
            >
                <Link :href="home()">
                    <ArrowLeft />
                    Volver al catálogo
                </Link>
            </Button>

            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div class="space-y-2">
                    <p
                        class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Reservá tu turno
                    </p>
                    <h1
                        class="text-3xl font-semibold tracking-tight sm:text-4xl"
                    >
                        {{ service.name }}
                    </h1>
                    <p
                        v-if="service.description"
                        class="max-w-xl text-sm text-muted-foreground"
                    >
                        {{ service.description }}
                    </p>
                </div>

                <dl class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                    <div class="flex items-center gap-2">
                        <Clock class="size-4 text-muted-foreground" />
                        <dt class="sr-only">Duración</dt>
                        <dd>{{ formatDuration(service.duration_minutes) }}</dd>
                    </div>
                    <div class="flex items-center gap-2">
                        <Wallet class="size-4 text-muted-foreground" />
                        <dt class="sr-only">Precio</dt>
                        <dd class="font-semibold">
                            {{ formatPrice(service.price) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <!-- Selección de día y horario -->
            <div class="space-y-8">
                <section aria-labelledby="dias-heading">
                    <div class="mb-3 flex items-baseline justify-between gap-4">
                        <h2
                            id="dias-heading"
                            class="text-base font-semibold tracking-tight"
                        >
                            Elegí el día
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Próximos {{ days.length }} días
                        </p>
                    </div>

                    <!-- Fila scrolleable: en mobile se desliza, en desktop entra casi entera. -->
                    <div
                        class="-mx-4 overflow-x-auto px-4 pb-2 sm:-mx-6 sm:px-6"
                    >
                        <ul class="flex min-w-max gap-2" role="list">
                            <li v-for="day in days" :key="day.date">
                                <button
                                    type="button"
                                    :disabled="day.slots === 0"
                                    :aria-current="
                                        day.date === selectedDate
                                            ? 'date'
                                            : undefined
                                    "
                                    :class="[
                                        'flex w-20 flex-col items-center gap-0.5 rounded-lg border px-2 py-3 text-center transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                                        day.date === selectedDate
                                            ? 'border-primary bg-primary text-primary-foreground shadow-sm'
                                            : 'border-border bg-card hover:bg-accent hover:text-accent-foreground',
                                        day.slots === 0 &&
                                            'cursor-not-allowed opacity-40 hover:bg-card hover:text-foreground',
                                    ]"
                                    @click="selectDay(day)"
                                >
                                    <span
                                        class="text-[11px] font-medium tracking-wide uppercase"
                                        :class="
                                            day.date === selectedDate
                                                ? 'text-primary-foreground/80'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{ shortWeekday(day.weekday) }}
                                    </span>
                                    <span
                                        class="text-sm font-semibold tabular-nums"
                                    >
                                        {{ day.label }}
                                    </span>
                                    <span
                                        class="text-[11px] leading-tight"
                                        :class="
                                            day.date === selectedDate
                                                ? 'text-primary-foreground/80'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{ slotsLabel(day) }}
                                    </span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </section>

                <section aria-labelledby="horarios-heading">
                    <div class="mb-3 flex items-baseline justify-between gap-4">
                        <h2
                            id="horarios-heading"
                            class="text-base font-semibold tracking-tight"
                        >
                            Elegí un horario
                        </h2>
                        <p
                            v-if="selectedDay"
                            class="text-xs text-muted-foreground capitalize"
                        >
                            {{ selectedDay.weekday }} {{ selectedDay.label }}
                        </p>
                    </div>

                    <!-- El choque de turnos: alguien reservó este hueco primero. -->
                    <Alert
                        v-if="slotError"
                        variant="destructive"
                        class="mb-4 border-destructive/40"
                    >
                        <TriangleAlert />
                        <AlertTitle>Ese horario se acaba de ocupar</AlertTitle>
                        <AlertDescription>
                            {{ slotError }} La grilla ya se actualizó: elegí
                            otro de los horarios que siguen libres.
                        </AlertDescription>
                    </Alert>

                    <div
                        v-if="slots.length"
                        class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5"
                    >
                        <button
                            v-for="slot in slots"
                            :key="slot.starts_at"
                            type="button"
                            :aria-pressed="selectedStart === slot.starts_at"
                            :class="[
                                'rounded-md border px-2 py-2.5 text-sm font-medium tabular-nums transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                                selectedStart === slot.starts_at
                                    ? 'border-primary bg-primary text-primary-foreground shadow-sm'
                                    : 'border-border bg-card hover:bg-accent hover:text-accent-foreground',
                            ]"
                            @click="selectSlot(slot)"
                        >
                            {{ slot.label }}
                        </button>
                    </div>

                    <Card v-else class="border-dashed">
                        <CardContent
                            class="flex flex-col items-center gap-3 py-10 text-center"
                        >
                            <span
                                class="flex size-11 items-center justify-center rounded-full bg-muted text-muted-foreground"
                            >
                                <CalendarX class="size-5" />
                            </span>
                            <div class="space-y-1">
                                <p class="font-medium">
                                    No quedan turnos disponibles para este día.
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{
                                        totalSlots > 0
                                            ? 'Probá con otra fecha de la fila de arriba.'
                                            : 'Por ahora no hay disponibilidad en los próximos días. Volvé a intentar más tarde.'
                                    }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </section>
            </div>

            <!-- Confirmación -->
            <aside class="lg:sticky lg:top-24">
                <Card class="gap-0 py-0">
                    <div class="space-y-4 px-6 py-6">
                        <h2 class="text-base font-semibold tracking-tight">
                            Tu turno
                        </h2>

                        <dl class="space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-muted-foreground">Servicio</dt>
                                <dd class="text-right font-medium">
                                    {{ service.name }}
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-muted-foreground">Día</dt>
                                <dd class="text-right font-medium capitalize">
                                    <template v-if="selectedDay">
                                        {{ selectedDay.weekday }}
                                        {{ selectedDay.label }}
                                    </template>
                                    <span v-else class="text-muted-foreground">
                                        —
                                    </span>
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-muted-foreground">Horario</dt>
                                <dd
                                    class="text-right font-medium tabular-nums"
                                    :class="
                                        !selectedSlot && 'text-muted-foreground'
                                    "
                                >
                                    <template v-if="selectedSlot">
                                        {{ selectedSlot.label }} a
                                        {{ timeOf(selectedSlot.ends_at) }}
                                    </template>
                                    <template v-else>
                                        Elegí un horario
                                    </template>
                                </dd>
                            </div>
                        </dl>

                        <Separator />

                        <div class="flex items-baseline justify-between gap-4">
                            <span class="text-sm text-muted-foreground">
                                Total
                            </span>
                            <span class="text-xl font-semibold tracking-tight">
                                {{ formatPrice(service.price) }}
                            </span>
                        </div>
                    </div>

                    <Separator />

                    <!-- Visitante sin cuenta: puede mirar la disponibilidad, no confirmar. -->
                    <div v-if="!user" class="space-y-4 px-6 py-6">
                        <div class="flex gap-3">
                            <Lock
                                class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                            />
                            <p class="text-sm text-muted-foreground">
                                Para confirmar el turno necesitás una cuenta.
                                Así podés ver, cambiar o cancelar tus reservas
                                cuando quieras.
                            </p>
                        </div>
                        <Button as-child class="w-full">
                            <Link :href="login()">
                                Iniciar sesión para reservar
                            </Link>
                        </Button>
                    </div>

                    <Form
                        v-else
                        :action="bookingStore().url"
                        method="post"
                        :options="{ preserveScroll: true }"
                        v-slot="{ errors, processing }"
                        class="space-y-4 px-6 py-6"
                    >
                        <input
                            type="hidden"
                            name="service_id"
                            :value="service.id"
                        />
                        <input
                            type="hidden"
                            name="starts_at"
                            :value="selectedSlot?.starts_at ?? ''"
                        />

                        <div class="grid gap-2">
                            <Label for="notes">
                                Comentarios
                                <span class="text-muted-foreground">
                                    (opcional)
                                </span>
                            </Label>
                            <textarea
                                id="notes"
                                name="notes"
                                v-model="notes"
                                rows="3"
                                :maxlength="NOTES_MAX_LENGTH"
                                placeholder="Contanos algo que nos sirva saber antes del turno."
                                class="min-h-20 w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30"
                            />
                            <p class="text-right text-xs text-muted-foreground">
                                {{ notes.length }} / {{ NOTES_MAX_LENGTH }}
                            </p>
                            <InputError :message="errors.notes" />
                        </div>

                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="!selectedSlot || processing"
                        >
                            <Spinner v-if="processing" />
                            Confirmar turno
                        </Button>

                        <p
                            v-if="!selectedSlot"
                            class="text-center text-xs text-muted-foreground"
                        >
                            Elegí un horario para poder confirmar.
                        </p>
                    </Form>
                </Card>

                <p
                    class="mt-4 flex items-start gap-2 text-xs leading-relaxed text-muted-foreground"
                >
                    <CalendarDays class="mt-0.5 size-3.5 shrink-0" />
                    <span>
                        Los horarios se calculan en tiempo real a partir de la
                        agenda del salón y de las reservas ya confirmadas, en
                        pasos de {{ stepMinutes }} minutos.
                    </span>
                </p>
            </aside>
        </div>
    </div>
</template>
