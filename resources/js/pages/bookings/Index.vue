<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Banknote,
    CalendarDays,
    CalendarPlus,
    Clock,
    StickyNote,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { home } from '@/routes';
import { destroy } from '@/routes/bookings';
import type { BookingStatus, ClientBooking } from '@/types/booking';

const props = defineProps<{
    upcoming: ClientBooking[];
    past: ClientBooking[];
}>();

const money = new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    maximumFractionDigits: 0,
});

const formatPrice = (price: string) => money.format(Number(price));

/** 90 → "1 h 30 min", 60 → "1 h", 45 → "45 min". */
function formatDuration(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    if (hours === 0) {
        return `${rest} min`;
    }

    return rest === 0 ? `${hours} h` : `${hours} h ${rest} min`;
}

const statusVariant = (
    status: BookingStatus,
): 'default' | 'secondary' | 'destructive' => {
    switch (status) {
        case 'cancelled':
            return 'destructive';
        case 'completed':
            return 'secondary';
        default:
            return 'default';
    }
};

const hasPast = computed(() => props.past.length > 0);

// Cancelar un turno es destructivo e irreversible, así que pasa sí o sí por un
// diálogo de confirmación: la reserva elegida vive acá hasta que se confirme.
const pending = ref<ClientBooking | null>(null);
const cancelling = ref(false);

const isDialogOpen = computed({
    get: () => pending.value !== null,
    set: (open: boolean) => {
        if (!open && !cancelling.value) {
            pending.value = null;
        }
    },
});

function askToCancel(booking: ClientBooking): void {
    pending.value = booking;
}

function confirmCancel(): void {
    if (!pending.value || cancelling.value) {
        return;
    }

    cancelling.value = true;

    router.delete(destroy(pending.value.id), {
        // La lista se recarga en el lugar: el usuario no pierde el scroll.
        preserveScroll: true,
        onFinish: () => {
            cancelling.value = false;
            pending.value = null;
        },
    });
}
</script>

<template>
    <Head title="Mis reservas" />

    <div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
        <header class="mb-10">
            <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">
                Mis reservas
            </h1>
            <p class="mt-2 max-w-2xl text-muted-foreground">
                Acá tenés tus turnos en Nova Studio. Podés cancelar hasta un
                rato antes de que empiecen; el horario vuelve a quedar libre
                para otra persona.
            </p>
        </header>

        <!-- Próximos turnos -->
        <section aria-labelledby="upcoming-heading">
            <div class="mb-4 flex items-center gap-3">
                <h2 id="upcoming-heading" class="text-lg font-semibold">
                    Próximos turnos
                </h2>
                <Badge v-if="upcoming.length" variant="secondary">
                    {{ upcoming.length }}
                </Badge>
            </div>

            <ul v-if="upcoming.length" class="grid gap-4">
                <li
                    v-for="booking in upcoming"
                    :key="booking.id"
                    class="rounded-xl border border-border bg-card p-5 shadow-xs sm:p-6"
                >
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div class="min-w-0 space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3
                                    class="text-base font-semibold"
                                    :class="
                                        booking.status === 'cancelled' &&
                                        'text-muted-foreground line-through'
                                    "
                                >
                                    {{ booking.service }}
                                </h3>
                                <Badge :variant="statusVariant(booking.status)">
                                    {{ booking.status_label }}
                                </Badge>
                            </div>

                            <div
                                class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-sm text-muted-foreground"
                            >
                                <span class="flex items-center gap-1.5">
                                    <CalendarDays class="size-4 shrink-0" />
                                    <span class="first-letter:uppercase">
                                        {{ booking.date_label }}
                                    </span>
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <Clock class="size-4 shrink-0" />
                                    {{ booking.time_label }} ·
                                    {{
                                        formatDuration(booking.duration_minutes)
                                    }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <Banknote class="size-4 shrink-0" />
                                    {{ formatPrice(booking.price) }}
                                </span>
                            </div>

                            <p
                                v-if="booking.notes"
                                class="flex gap-2 rounded-lg bg-muted px-3 py-2 text-sm text-muted-foreground"
                            >
                                <StickyNote class="mt-0.5 size-4 shrink-0" />
                                <span>{{ booking.notes }}</span>
                            </p>
                        </div>

                        <Button
                            v-if="booking.can_cancel"
                            variant="outline"
                            size="sm"
                            class="shrink-0 self-start"
                            @click="askToCancel(booking)"
                        >
                            <X />
                            Cancelar turno
                        </Button>
                    </div>
                </li>
            </ul>

            <!-- Estado vacío -->
            <div
                v-else
                class="flex flex-col items-center gap-4 rounded-xl border border-dashed border-border bg-card px-6 py-14 text-center"
            >
                <span
                    class="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
                >
                    <CalendarPlus class="size-6" />
                </span>
                <div class="space-y-1">
                    <p class="font-medium">Todavía no tenés turnos agendados</p>
                    <p class="max-w-md text-sm text-muted-foreground">
                        Elegí un servicio y quedate con el horario que mejor te
                        venga. Te lleva menos de un minuto.
                    </p>
                </div>
                <Button as-child>
                    <Link :href="home()">Reservar un turno</Link>
                </Button>
            </div>
        </section>

        <!-- Historial: mismo contenido, presencia visual más apagada. -->
        <section
            v-if="hasPast"
            aria-labelledby="past-heading"
            class="mt-12 border-t border-border pt-10"
        >
            <div class="mb-4 flex items-center gap-3">
                <h2
                    id="past-heading"
                    class="text-lg font-semibold text-muted-foreground"
                >
                    Historial
                </h2>
                <Badge variant="outline">{{ past.length }}</Badge>
            </div>

            <ul class="grid gap-3">
                <li
                    v-for="booking in past"
                    :key="booking.id"
                    class="rounded-xl border border-border/60 bg-muted/30 p-5"
                >
                    <div class="min-w-0 space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3
                                class="text-base font-medium text-muted-foreground"
                                :class="
                                    booking.status === 'cancelled' &&
                                    'line-through'
                                "
                            >
                                {{ booking.service }}
                            </h3>
                            <Badge :variant="statusVariant(booking.status)">
                                {{ booking.status_label }}
                            </Badge>
                        </div>

                        <div
                            class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-sm text-muted-foreground"
                        >
                            <span class="flex items-center gap-1.5">
                                <CalendarDays class="size-4 shrink-0" />
                                <span class="first-letter:uppercase">
                                    {{ booking.date_label }}
                                </span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <Clock class="size-4 shrink-0" />
                                {{ booking.time_label }} ·
                                {{ formatDuration(booking.duration_minutes) }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <Banknote class="size-4 shrink-0" />
                                {{ formatPrice(booking.price) }}
                            </span>
                        </div>

                        <p
                            v-if="booking.notes"
                            class="flex gap-2 text-sm text-muted-foreground/80"
                        >
                            <StickyNote class="mt-0.5 size-4 shrink-0" />
                            <span>{{ booking.notes }}</span>
                        </p>
                    </div>
                </li>
            </ul>
        </section>
    </div>

    <Dialog v-model:open="isDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>¿Cancelar este turno?</DialogTitle>
                <DialogDescription>
                    <template v-if="pending">
                        Vas a cancelar
                        <span class="font-medium text-foreground">
                            {{ pending.service }}
                        </span>
                        del
                        <span class="font-medium text-foreground">
                            {{ pending.date_label }}
                        </span>
                        a las
                        <span class="font-medium text-foreground">
                            {{ pending.time_label }}
                        </span>
                        . El horario queda libre para otra persona y esta acción
                        no se puede deshacer.
                    </template>
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <Button
                    variant="outline"
                    :disabled="cancelling"
                    @click="pending = null"
                >
                    Volver
                </Button>
                <Button
                    variant="destructive"
                    :disabled="cancelling"
                    @click="confirmCancel"
                >
                    <Spinner v-if="cancelling" />
                    Sí, cancelar turno
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
