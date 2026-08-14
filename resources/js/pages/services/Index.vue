<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, Clock, Scissors, Sparkles } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { create as bookingCreate } from '@/routes/bookings';
import type { ScheduleDay, Service } from '@/types/booking';

const props = defineProps<{
    services: Service[];
    schedule: ScheduleDay[];
}>();

/** Los precios llegan como decimal string desde Laravel, así que hay que castear. */
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

const hasServices = computed(() => props.services.length > 0);

const steps = [
    {
        icon: Scissors,
        title: 'Elegí el servicio',
        description: 'Cada uno tiene su duración y su precio, sin sorpresas.',
    },
    {
        icon: CalendarDays,
        title: 'Elegí día y horario',
        description:
            'Sólo vas a ver los huecos que están realmente libres hoy.',
    },
    {
        icon: Sparkles,
        title: 'Confirmá y listo',
        description: 'Tu turno queda guardado y lo podés cancelar cuando quieras.',
    },
];

/** Scroll suave a una sección de la misma página, sin depender del CSS global. */
function scrollToSection(event: MouseEvent, id: string): void {
    const target = document.getElementById(id);

    if (!target) {
        return;
    }

    event.preventDefault();
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<template>
    <Head title="Turnos online" />

    <!-- Hero -->
    <section class="relative isolate overflow-hidden border-b border-border/60">
        <!-- Detalle geométrico: retícula tenue difuminada hacia los bordes. -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(to_right,var(--border)_1px,transparent_1px),linear-gradient(to_bottom,var(--border)_1px,transparent_1px)] bg-[size:56px_56px] opacity-60 [mask-image:radial-gradient(ellipse_60%_60%_at_50%_0%,black,transparent)]"
        />
        <div
            aria-hidden="true"
            class="pointer-events-none absolute -top-32 left-1/2 -z-10 size-[32rem] -translate-x-1/2 rounded-full bg-primary/10 blur-3xl"
        />

        <div class="mx-auto w-full max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
            <div class="max-w-2xl">
                <Badge variant="outline" class="mb-5 bg-background/60">
                    <Sparkles />
                    Reservá online, 24/7
                </Badge>

                <h1
                    class="text-balance text-4xl font-semibold tracking-tight sm:text-5xl lg:text-6xl"
                >
                    Tu próximo turno en
                    <span class="block text-muted-foreground">
                        Nova Studio.
                    </span>
                </h1>

                <p
                    class="mt-5 max-w-xl text-pretty text-base leading-relaxed text-muted-foreground sm:text-lg"
                >
                    Elegí el servicio, mirá la disponibilidad real de la agenda y
                    quedate con el horario que mejor te venga. Sin llamadas, sin
                    idas y vueltas por mensaje.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <Button
                        v-if="hasServices"
                        as-child
                        size="lg"
                        class="min-w-44"
                    >
                        <a href="#servicios" @click="scrollToServices">
                            Reservá tu turno
                        </a>
                    </Button>
                    <Button as-child variant="outline" size="lg">
                        <a href="#horarios" @click="scrollToServices">
                            Ver horarios de atención
                        </a>
                    </Button>
                </div>
            </div>

            <!-- Cómo funciona -->
            <ul
                class="mt-14 grid gap-6 border-t border-border/60 pt-10 sm:grid-cols-3 sm:gap-8"
            >
                <li v-for="step in steps" :key="step.title" class="flex gap-3.5">
                    <span
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-border bg-card text-muted-foreground"
                    >
                        <component :is="step.icon" class="size-4.5" />
                    </span>
                    <div class="space-y-1">
                        <p class="text-sm font-medium leading-none">
                            {{ step.title }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ step.description }}
                        </p>
                    </div>
                </li>
            </ul>
        </div>
    </section>

    <!-- Catálogo -->
    <section
        id="servicios"
        class="mx-auto w-full max-w-6xl scroll-mt-20 px-4 py-10 sm:px-6 sm:py-14"
    >
        <header class="mb-8 flex flex-col gap-2">
            <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                Nuestros servicios
            </h2>
            <p class="max-w-2xl text-sm text-muted-foreground sm:text-base">
                Precios y duraciones actualizados. La duración define cómo se
                trocea la agenda, así que lo que ves es lo que dura tu turno.
            </p>
        </header>

        <div
            v-if="hasServices"
            class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
        >
            <Card
                v-for="service in services"
                :key="service.id"
                class="flex flex-col transition-shadow hover:shadow-md"
            >
                <CardHeader>
                    <CardTitle class="text-lg">{{ service.name }}</CardTitle>
                    <CardDescription v-if="service.description">
                        {{ service.description }}
                    </CardDescription>
                </CardHeader>

                <CardContent class="flex-1">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <span
                            class="inline-flex items-center gap-1.5 text-sm text-muted-foreground"
                        >
                            <Clock class="size-4" />
                            {{ formatDuration(service.duration_minutes) }}
                        </span>
                        <span class="text-xl font-semibold tracking-tight">
                            {{ formatPrice(service.price) }}
                        </span>
                    </div>
                </CardContent>

                <CardFooter>
                    <Button as-child class="w-full">
                        <Link :href="bookingCreate(service.slug)">
                            Reservar turno
                        </Link>
                    </Button>
                </CardFooter>
            </Card>
        </div>

        <!-- Estado vacío -->
        <Card v-else class="border-dashed">
            <CardContent
                class="flex flex-col items-center gap-3 py-12 text-center"
            >
                <span
                    class="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
                >
                    <Scissors class="size-5" />
                </span>
                <div class="space-y-1">
                    <p class="font-medium">Todavía no hay servicios cargados</p>
                    <p class="mx-auto max-w-md text-sm text-muted-foreground">
                        Estamos actualizando la carta de servicios. Volvé en un
                        rato y vas a poder reservar tu turno.
                    </p>
                </div>
            </CardContent>
        </Card>
    </section>

    <!-- Horarios de atención -->
    <section
        id="horarios"
        class="border-t border-border/60 bg-muted/30 scroll-mt-20"
    >
        <div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_1.4fr]">
                <div>
                    <h2
                        class="text-2xl font-semibold tracking-tight sm:text-3xl"
                    >
                        Horarios de atención
                    </h2>
                    <p class="mt-3 text-sm text-muted-foreground">
                        La disponibilidad se calcula sobre estas ventanas: si un
                        horario no aparece al reservar, es porque ya está tomado
                        o el turno no entra completo antes de cerrar.
                    </p>
                </div>

                <Card v-if="schedule.length" class="gap-0 py-2">
                    <CardContent class="px-0">
                        <ul>
                            <li
                                v-for="(day, index) in schedule"
                                :key="day.day"
                                class="px-6"
                            >
                                <Separator v-if="index > 0" />
                                <div
                                    class="flex flex-col gap-1 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:gap-6"
                                >
                                    <span class="text-sm font-medium">
                                        {{ day.day }}
                                    </span>
                                    <span
                                        class="text-sm tabular-nums text-muted-foreground"
                                    >
                                        {{ day.ranges.join(' · ') }}
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card v-else class="border-dashed">
                    <CardContent
                        class="py-10 text-center text-sm text-muted-foreground"
                    >
                        Todavía no cargamos los horarios de atención.
                    </CardContent>
                </Card>
            </div>
        </div>
    </section>
</template>
