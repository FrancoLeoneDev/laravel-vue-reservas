<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { CalendarOff, Clock, Info, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    destroy,
    index as availabilityIndex,
    store,
    update,
} from '@/routes/admin/availability';
import type { AvailabilityDay, AvailabilityWindow } from '@/types';

defineProps<{
    days: AvailabilityDay[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Disponibilidad',
                href: availabilityIndex(),
            },
        ],
    },
});

const draft = reactive({
    day_of_week: 1,
    day_name: 'Lunes',
    start_time: '09:00',
    end_time: '13:00',
    is_active: true,
});

const formOpen = ref(false);
const editing = ref<AvailabilityWindow | null>(null);

const formAction = computed(() =>
    editing.value ? update(editing.value.id) : store(),
);

function openCreate(day: AvailabilityDay): void {
    editing.value = null;
    Object.assign(draft, {
        day_of_week: day.day_of_week,
        day_name: day.name,
        start_time: '09:00',
        end_time: '13:00',
        is_active: true,
    });
    formOpen.value = true;
}

function openEdit(day: AvailabilityDay, window: AvailabilityWindow): void {
    editing.value = window;
    Object.assign(draft, {
        day_of_week: day.day_of_week,
        day_name: day.name,
        start_time: window.start_time,
        end_time: window.end_time,
        is_active: window.is_active,
    });
    formOpen.value = true;
}

const pendingDelete = ref<{
    day: AvailabilityDay;
    window: AvailabilityWindow;
} | null>(null);

function confirmDelete(): void {
    if (!pendingDelete.value) {
        return;
    }

    router.delete(destroy(pendingDelete.value.window.id), {
        preserveScroll: true,
    });
    pendingDelete.value = null;
}
</script>

<template>
    <Head title="Disponibilidad" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <header>
            <h1 class="text-xl font-semibold text-foreground">
                Disponibilidad
            </h1>
            <p class="text-sm text-muted-foreground">
                Los horarios de atención de la peluquería, día por día.
            </p>
        </header>

        <Alert>
            <Info />
            <AlertTitle
                >Desde acá se calculan los huecos reservables</AlertTitle
            >
            <AlertDescription>
                El sistema parte de estos tramos, les resta los turnos ya
                tomados y muestra al cliente lo que queda libre. Si cambiás algo
                afecta la disponibilidad de acá en adelante: los turnos ya
                confirmados siguen en pie y se mantienen en la agenda.
            </AlertDescription>
        </Alert>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Card
                v-for="day in days"
                :key="day.day_of_week"
                class="gap-3 py-4"
                :class="day.windows.length === 0 ? 'bg-muted/40' : ''"
            >
                <CardHeader class="px-4">
                    <div class="flex items-center justify-between gap-2">
                        <CardTitle class="text-base">{{ day.name }}</CardTitle>
                        <Badge
                            v-if="day.windows.length === 0"
                            variant="outline"
                        >
                            <CalendarOff />
                            Cerrado
                        </Badge>
                    </div>
                </CardHeader>

                <CardContent class="flex flex-col gap-2 px-4">
                    <p
                        v-if="day.windows.length === 0"
                        class="rounded-md border border-dashed border-border px-3 py-4 text-center text-sm text-muted-foreground"
                    >
                        Sin tramos cargados: ese día no se puede reservar.
                    </p>

                    <div
                        v-for="window in day.windows"
                        :key="window.id"
                        class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-border bg-background px-3 py-2"
                        :class="window.is_active ? '' : 'opacity-60'"
                    >
                        <div class="flex items-center gap-2">
                            <Clock class="size-4 text-muted-foreground" />
                            <span
                                class="text-sm font-medium text-foreground tabular-nums"
                            >
                                {{ window.start_time }} – {{ window.end_time }}
                            </span>
                            <Badge
                                :variant="
                                    window.is_active ? 'outline' : 'secondary'
                                "
                                :class="
                                    window.is_active
                                        ? 'border-emerald-600/30 bg-emerald-500/10 text-emerald-700 dark:border-emerald-400/30 dark:text-emerald-400'
                                        : ''
                                "
                            >
                                {{ window.is_active ? 'Activo' : 'Inactivo' }}
                            </Badge>
                        </div>

                        <div class="flex items-center gap-1">
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                :aria-label="`Editar el tramo de ${window.start_time} del ${day.name}`"
                                @click="openEdit(day, window)"
                            >
                                <Pencil />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="text-muted-foreground hover:text-destructive"
                                :aria-label="`Eliminar el tramo de ${window.start_time} del ${day.name}`"
                                @click="pendingDelete = { day, window }"
                            >
                                <Trash2 />
                            </Button>
                        </div>
                    </div>

                    <Button
                        variant="outline"
                        size="sm"
                        class="mt-1 w-full"
                        @click="openCreate(day)"
                    >
                        <Plus />
                        Agregar tramo
                    </Button>
                </CardContent>
            </Card>
        </section>
    </div>

    <Dialog v-model:open="formOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>
                    {{ editing ? 'Editar tramo' : 'Agregar tramo' }} —
                    {{ draft.day_name }}
                </DialogTitle>
                <DialogDescription>
                    La hora de cierre tiene que ser posterior a la de apertura y
                    no puede haber dos tramos que arranquen a la misma hora el
                    mismo día.
                </DialogDescription>
            </DialogHeader>

            <Form
                :action="formAction"
                :options="{ preserveScroll: true }"
                class="space-y-4"
                v-slot="{ errors, processing }"
                @success="formOpen = false"
            >
                <input
                    type="hidden"
                    name="day_of_week"
                    :value="draft.day_of_week"
                />
                <InputError :message="errors.day_of_week" />

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="window-start">Abre</Label>
                        <Input
                            id="window-start"
                            v-model="draft.start_time"
                            name="start_time"
                            type="time"
                            required
                        />
                        <InputError :message="errors.start_time" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="window-end">Cierra</Label>
                        <Input
                            id="window-end"
                            v-model="draft.end_time"
                            name="end_time"
                            type="time"
                            required
                        />
                        <InputError :message="errors.end_time" />
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-md bg-muted/50 p-3">
                    <Checkbox
                        id="window-active"
                        :model-value="draft.is_active"
                        @update:model-value="
                            (value) => (draft.is_active = value === true)
                        "
                    />
                    <input
                        type="hidden"
                        name="is_active"
                        :value="draft.is_active ? '1' : '0'"
                    />
                    <div class="grid gap-1">
                        <Label for="window-active">Tramo activo</Label>
                        <p class="text-xs text-muted-foreground">
                            Desactivalo para dejar de ofrecer turnos en esa
                            franja sin tener que borrarla.
                        </p>
                        <InputError :message="errors.is_active" />
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="formOpen = false"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="processing">
                        {{ editing ? 'Guardar cambios' : 'Agregar tramo' }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>

    <Dialog
        :open="pendingDelete !== null"
        @update:open="(open: boolean) => !open && (pendingDelete = null)"
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>¿Eliminar este tramo?</DialogTitle>
                <DialogDescription>
                    <template v-if="pendingDelete">
                        Se borra la franja de
                        <strong class="text-foreground">
                            {{ pendingDelete.window.start_time }} a
                            {{ pendingDelete.window.end_time }}
                        </strong>
                        del {{ pendingDelete.day.name }}. Dejan de generarse
                        huecos nuevos ahí, pero los turnos ya confirmados dentro
                        de esa franja siguen vigentes.
                    </template>
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="pendingDelete = null">
                    Volver
                </Button>
                <Button variant="destructive" @click="confirmDelete">
                    Sí, eliminar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
