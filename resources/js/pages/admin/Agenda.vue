<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Ban,
    CalendarDays,
    Check,
    ChevronLeft,
    ChevronRight,
    CircleCheck,
    CircleDollarSign,
    CircleX,
    Clock,
    ListChecks,
    Mail,
    Phone,
    StickyNote,
    TriangleAlert,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import type { BadgeVariants } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { agenda } from '@/routes/admin';
import { status } from '@/routes/admin/bookings';
import type { AgendaBooking, AgendaDay, AgendaStats } from '@/types';

const props = defineProps<{
    view: 'day' | 'week';
    date: string;
    rangeLabel: string;
    previousDate: string;
    nextDate: string;
    today: string;
    days: AgendaDay[];
    stats: AgendaStats;
    statuses: { value: string; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Agenda',
                href: agenda(),
            },
        ],
    },
});

const page = usePage();

/** El backend devuelve el error de estado por `back()->withErrors(['status' => ...])`. */
const statusError = computed(
    () => page.props.errors?.status as string | undefined,
);

const money = new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    maximumFractionDigits: 0,
});

function formatMoney(value: number | string): string {
    return money.format(Number(value));
}

/** 135 → "2h 15m". Se muestra así porque el dueño piensa en horas, no en minutos. */
function formatHours(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    if (hours === 0) {
        return `${rest}m`;
    }

    return rest === 0 ? `${hours}h` : `${hours}h ${rest}m`;
}

type StatCard = {
    key: keyof AgendaStats;
    label: string;
    icon: LucideIcon;
    format: (value: number) => string;
};

const statCards: StatCard[] = [
    {
        key: 'total',
        label: 'Turnos',
        icon: CalendarDays,
        format: (value) => String(value),
    },
    {
        key: 'confirmed',
        label: 'Confirmados',
        icon: Check,
        format: (value) => String(value),
    },
    {
        key: 'completed',
        label: 'Completados',
        icon: ListChecks,
        format: (value) => String(value),
    },
    {
        key: 'cancelled',
        label: 'Cancelados',
        icon: CircleX,
        format: (value) => String(value),
    },
    {
        key: 'revenue',
        label: 'Ingresos estimados',
        icon: CircleDollarSign,
        format: formatMoney,
    },
    {
        key: 'minutes',
        label: 'Horas ocupadas',
        icon: Clock,
        format: formatHours,
    },
];

const statusIcons: Record<string, LucideIcon> = {
    confirmed: Check,
    completed: CircleCheck,
    cancelled: Ban,
};

function badgeVariant(value: string): BadgeVariants['variant'] {
    if (value === 'cancelled') {
        return 'destructive';
    }

    return value === 'completed' ? 'outline' : 'default';
}

/**
 * El verde de "completada" no existe como token de shadcn, así que se arma con
 * opacidades sobre la paleta y su variante dark para que el modo oscuro siga bien.
 */
function badgeClass(value: string): string {
    return value === 'completed'
        ? 'border-emerald-600/30 bg-emerald-500/10 text-emerald-700 dark:border-emerald-400/30 dark:text-emerald-400'
        : '';
}

function go(view: 'day' | 'week', date: string): void {
    router.get(
        agenda(),
        { view, date },
        { preserveState: true, preserveScroll: true },
    );
}

const pendingCancel = ref<AgendaBooking | null>(null);

function submitStatus(id: number, value: string): void {
    router.patch(status(id), { status: value }, { preserveScroll: true });
}

function selectStatus(booking: AgendaBooking, value: string): void {
    if (booking.status === value) {
        return;
    }

    // Cancelar es la única acción que le arruina el día a un cliente: se confirma.
    if (value === 'cancelled') {
        pendingCancel.value = booking;

        return;
    }

    submitStatus(booking.id, value);
}

function confirmCancel(): void {
    if (!pendingCancel.value) {
        return;
    }

    submitStatus(pendingCancel.value.id, 'cancelled');
    pendingCancel.value = null;
}

const isWeek = computed(() => props.view === 'week');
</script>

<template>
    <Head title="Agenda" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <header
            class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
        >
            <div>
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    {{ isWeek ? 'Semana' : 'Día' }}
                </p>
                <h1
                    class="text-xl font-semibold text-foreground first-letter:uppercase"
                >
                    {{ rangeLabel }}
                </h1>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div
                    class="inline-flex rounded-md border border-border bg-muted p-0.5"
                    role="group"
                    aria-label="Cambiar vista"
                >
                    <button
                        type="button"
                        class="rounded-sm px-3 py-1 text-sm font-medium transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        :class="
                            !isWeek
                                ? 'bg-background text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        :aria-pressed="!isWeek"
                        @click="go('day', date)"
                    >
                        Día
                    </button>
                    <button
                        type="button"
                        class="rounded-sm px-3 py-1 text-sm font-medium transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        :class="
                            isWeek
                                ? 'bg-background text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        :aria-pressed="isWeek"
                        @click="go('week', date)"
                    >
                        Semana
                    </button>
                </div>

                <div class="flex items-center gap-1">
                    <Button
                        variant="outline"
                        size="icon"
                        :aria-label="
                            isWeek ? 'Semana anterior' : 'Día anterior'
                        "
                        @click="go(view, previousDate)"
                    >
                        <ChevronLeft />
                    </Button>
                    <Button variant="outline" @click="go(view, today)">
                        Hoy
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        :aria-label="
                            isWeek ? 'Semana siguiente' : 'Día siguiente'
                        "
                        @click="go(view, nextDate)"
                    >
                        <ChevronRight />
                    </Button>
                </div>
            </div>
        </header>

        <Alert v-if="statusError" variant="destructive">
            <TriangleAlert />
            <AlertTitle>No se pudo cambiar el estado</AlertTitle>
            <AlertDescription>{{ statusError }}</AlertDescription>
        </Alert>

        <section
            class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6"
            aria-label="Métricas del período"
        >
            <Card
                v-for="card in statCards"
                :key="card.key"
                class="gap-2 py-4 shadow-none"
            >
                <CardHeader class="px-4">
                    <div
                        class="flex items-center gap-2 text-xs font-medium text-muted-foreground"
                    >
                        <component :is="card.icon" class="size-4" />
                        {{ card.label }}
                    </div>
                </CardHeader>
                <CardContent class="px-4">
                    <p class="text-2xl font-semibold text-foreground">
                        {{ card.format(stats[card.key]) }}
                    </p>
                </CardContent>
            </Card>
        </section>

        <section
            class="grid gap-4"
            :class="isWeek ? 'lg:grid-cols-7' : 'grid-cols-1'"
        >
            <Card
                v-for="day in days"
                :key="day.date"
                class="gap-3 py-4"
                :class="[
                    day.is_today
                        ? 'border-primary/60 ring-1 ring-primary/20'
                        : '',
                    !day.is_open && day.bookings.length === 0
                        ? 'bg-muted/40'
                        : '',
                ]"
            >
                <CardHeader class="px-4">
                    <div class="flex items-center justify-between gap-2">
                        <h2
                            class="text-sm font-semibold text-foreground first-letter:uppercase"
                            :class="isWeek ? '' : 'text-base'"
                        >
                            {{ day.label }}
                        </h2>
                        <Badge
                            v-if="day.is_today"
                            variant="outline"
                            class="border-primary/40 text-primary"
                        >
                            Hoy
                        </Badge>
                    </div>
                    <p
                        v-if="!day.is_open"
                        class="text-xs text-muted-foreground"
                    >
                        Cerrado
                    </p>
                </CardHeader>

                <CardContent class="px-4">
                    <p
                        v-if="day.bookings.length === 0"
                        class="rounded-md border border-dashed border-border px-3 py-6 text-center text-sm text-muted-foreground"
                    >
                        {{ day.is_open ? 'Sin turnos' : 'Cerrado' }}
                    </p>

                    <ul v-else class="flex flex-col gap-2">
                        <li
                            v-for="booking in day.bookings"
                            :key="booking.id"
                            class="rounded-lg border border-border bg-background p-3"
                            :class="
                                booking.status === 'cancelled'
                                    ? 'opacity-60'
                                    : ''
                            "
                        >
                            <div
                                class="flex flex-col gap-2"
                                :class="
                                    isWeek
                                        ? ''
                                        : 'sm:flex-row sm:items-start sm:justify-between'
                                "
                            >
                                <div class="min-w-0 space-y-1">
                                    <p
                                        class="flex items-center gap-1.5 text-sm font-semibold text-foreground tabular-nums"
                                    >
                                        <Clock
                                            class="size-3.5 text-muted-foreground"
                                        />
                                        {{ booking.starts_at }} –
                                        {{ booking.ends_at }}
                                    </p>
                                    <p
                                        class="truncate text-sm text-foreground"
                                        :title="booking.service"
                                    >
                                        {{ booking.service }}
                                    </p>
                                    <p
                                        class="truncate text-sm text-muted-foreground"
                                    >
                                        {{ booking.client }}
                                    </p>

                                    <template v-if="!isWeek">
                                        <p
                                            class="flex items-center gap-1.5 text-xs text-muted-foreground"
                                        >
                                            <Mail class="size-3.5" />
                                            <a
                                                :href="`mailto:${booking.email}`"
                                                class="underline-offset-4 hover:underline"
                                            >
                                                {{ booking.email }}
                                            </a>
                                        </p>
                                        <p
                                            v-if="booking.phone"
                                            class="flex items-center gap-1.5 text-xs text-muted-foreground"
                                        >
                                            <Phone class="size-3.5" />
                                            <a
                                                :href="`tel:${booking.phone}`"
                                                class="underline-offset-4 hover:underline"
                                            >
                                                {{ booking.phone }}
                                            </a>
                                        </p>
                                        <p
                                            v-if="booking.notes"
                                            class="flex items-start gap-1.5 text-xs text-muted-foreground"
                                        >
                                            <StickyNote
                                                class="mt-0.5 size-3.5 shrink-0"
                                            />
                                            <span>{{ booking.notes }}</span>
                                        </p>
                                    </template>
                                </div>

                                <div
                                    class="flex shrink-0 items-center gap-2"
                                    :class="
                                        isWeek
                                            ? 'justify-between'
                                            : 'sm:flex-col sm:items-end'
                                    "
                                >
                                    <span
                                        class="text-sm font-medium text-foreground tabular-nums"
                                    >
                                        {{ formatMoney(booking.price) }}
                                    </span>

                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <button
                                                type="button"
                                                class="rounded-full focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                                :aria-label="`Cambiar estado del turno de ${booking.client}`"
                                            >
                                                <Badge
                                                    :variant="
                                                        badgeVariant(
                                                            booking.status,
                                                        )
                                                    "
                                                    :class="
                                                        badgeClass(
                                                            booking.status,
                                                        )
                                                    "
                                                >
                                                    {{ booking.status_label }}
                                                </Badge>
                                            </button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuLabel>
                                                Cambiar estado
                                            </DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                v-for="option in statuses"
                                                :key="option.value"
                                                :disabled="
                                                    option.value ===
                                                    booking.status
                                                "
                                                :variant="
                                                    option.value === 'cancelled'
                                                        ? 'destructive'
                                                        : 'default'
                                                "
                                                @select="
                                                    selectStatus(
                                                        booking,
                                                        option.value,
                                                    )
                                                "
                                            >
                                                <component
                                                    :is="
                                                        statusIcons[
                                                            option.value
                                                        ]
                                                    "
                                                    v-if="
                                                        statusIcons[
                                                            option.value
                                                        ]
                                                    "
                                                />
                                                {{ option.label }}
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </section>
    </div>

    <Dialog
        :open="pendingCancel !== null"
        @update:open="(open: boolean) => !open && (pendingCancel = null)"
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>¿Cancelar este turno?</DialogTitle>
                <DialogDescription>
                    <template v-if="pendingCancel">
                        Se cancela el turno de
                        <strong class="text-foreground">
                            {{ pendingCancel.client }}
                        </strong>
                        para {{ pendingCancel.service }} a las
                        {{ pendingCancel.starts_at }}. El hueco queda libre para
                        que lo tome otra persona.
                    </template>
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="pendingCancel = null">
                    Volver
                </Button>
                <Button variant="destructive" @click="confirmCancel">
                    Sí, cancelar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
