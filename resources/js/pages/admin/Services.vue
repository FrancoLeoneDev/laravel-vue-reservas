<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Clock, Info, Pencil, Plus, Scissors, Trash2 } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    destroy,
    index as servicesIndex,
    store,
    update,
} from '@/routes/admin/services';
import type { Service } from '@/types';

const props = defineProps<{
    services: Service[];
    stepMinutes: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Servicios',
                href: servicesIndex(),
            },
        ],
    },
});

const money = new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    maximumFractionDigits: 0,
});

function formatMoney(value: number | string): string {
    return money.format(Number(value));
}

function formatDuration(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    if (hours === 0) {
        return `${rest} min`;
    }

    return rest === 0 ? `${hours} h` : `${hours} h ${rest} min`;
}

/**
 * Duraciones sugeridas. Todas son múltiplos del paso de la grilla, así que el
 * formulario no puede generar una duración que el backend vaya a rechazar.
 */
const SUGGESTED_DURATIONS = [15, 30, 45, 60, 75, 90, 120, 150, 180];

const emptyDraft = () => ({
    name: '',
    description: '',
    duration_minutes: '30',
    price: '',
    is_active: true,
});

const draft = reactive(emptyDraft());

const formOpen = ref(false);
const editing = ref<Service | null>(null);

const durationOptions = computed(() => {
    const values = new Set(
        SUGGESTED_DURATIONS.filter(
            (minutes) => minutes % props.stepMinutes === 0,
        ),
    );

    const current = Number(draft.duration_minutes);

    if (Number.isFinite(current) && current > 0) {
        values.add(current);
    }

    return [...values].sort((a, b) => a - b);
});

const formAction = computed(() =>
    editing.value ? update(editing.value.id) : store(),
);

function openCreate(): void {
    editing.value = null;
    Object.assign(draft, emptyDraft());
    formOpen.value = true;
}

function openEdit(service: Service): void {
    editing.value = service;
    Object.assign(draft, {
        name: service.name,
        description: service.description ?? '',
        duration_minutes: String(service.duration_minutes),
        price: service.price,
        is_active: service.is_active ?? true,
    });
    formOpen.value = true;
}

const pendingDelete = ref<Service | null>(null);

function confirmDelete(): void {
    if (!pendingDelete.value) {
        return;
    }

    router.delete(destroy(pendingDelete.value.id), { preserveScroll: true });
    pendingDelete.value = null;
}

const ROW_GRID =
    'md:grid-cols-[minmax(0,3fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto]';
</script>

<template>
    <Head title="Servicios" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <header
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-xl font-semibold text-foreground">Servicios</h1>
                <p class="text-sm text-muted-foreground">
                    El catálogo que ve el cliente al reservar. La duración
                    define cuánto ocupa el turno en la agenda.
                </p>
            </div>

            <Button @click="openCreate">
                <Plus />
                Nuevo servicio
            </Button>
        </header>

        <Card class="gap-0 py-0">
            <div
                class="hidden gap-4 px-4 py-3 text-xs font-medium tracking-wide text-muted-foreground uppercase md:grid"
                :class="ROW_GRID"
            >
                <span>Servicio</span>
                <span>Duración</span>
                <span>Precio</span>
                <span>Reservas activas</span>
                <span class="sr-only">Acciones</span>
            </div>

            <p
                v-if="services.length === 0"
                class="px-4 py-10 text-center text-sm text-muted-foreground"
            >
                Todavía no hay servicios cargados. Creá el primero con el botón
                de arriba.
            </p>

            <div
                v-for="service in services"
                :key="service.id"
                class="grid gap-3 border-t border-border px-4 py-4 md:items-center md:gap-4"
                :class="ROW_GRID"
            >
                <div class="min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium text-foreground">
                            {{ service.name }}
                        </span>
                        <Badge
                            :variant="
                                service.is_active ? 'outline' : 'secondary'
                            "
                            :class="
                                service.is_active
                                    ? 'border-emerald-600/30 bg-emerald-500/10 text-emerald-700 dark:border-emerald-400/30 dark:text-emerald-400'
                                    : ''
                            "
                        >
                            {{ service.is_active ? 'Activo' : 'Inactivo' }}
                        </Badge>
                    </div>
                    <p
                        v-if="service.description"
                        class="text-sm text-muted-foreground"
                    >
                        {{ service.description }}
                    </p>
                </div>

                <p class="flex items-center gap-2 text-sm text-foreground">
                    <Clock class="size-4 text-muted-foreground md:hidden" />
                    <span class="text-muted-foreground md:hidden">
                        Duración:
                    </span>
                    {{ formatDuration(service.duration_minutes) }}
                </p>

                <p class="text-sm font-medium text-foreground tabular-nums">
                    <span class="font-normal text-muted-foreground md:hidden">
                        Precio:
                    </span>
                    {{ formatMoney(service.price) }}
                </p>

                <p class="text-sm text-foreground tabular-nums">
                    <span class="text-muted-foreground md:hidden">
                        Reservas activas:
                    </span>
                    {{ service.active_bookings_count ?? 0 }}
                </p>

                <div class="flex items-center gap-2 md:justify-end">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="openEdit(service)"
                    >
                        <Pencil />
                        Editar
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        class="text-muted-foreground hover:text-destructive"
                        :aria-label="`Eliminar ${service.name}`"
                        @click="pendingDelete = service"
                    >
                        <Trash2 />
                    </Button>
                </div>
            </div>
        </Card>
    </div>

    <Dialog v-model:open="formOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{ editing ? 'Editar servicio' : 'Nuevo servicio' }}
                </DialogTitle>
                <DialogDescription>
                    La duración tiene que ser múltiplo de
                    {{ stepMinutes }} minutos: es el paso con el que se arma la
                    grilla de turnos.
                </DialogDescription>
            </DialogHeader>

            <Form
                :action="formAction"
                :options="{ preserveScroll: true }"
                class="space-y-4"
                v-slot="{ errors, processing }"
                @success="formOpen = false"
            >
                <div class="grid gap-2">
                    <Label for="service-name">Nombre</Label>
                    <Input
                        id="service-name"
                        v-model="draft.name"
                        name="name"
                        maxlength="120"
                        required
                        placeholder="Corte de pelo"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="service-description">
                        Descripción (opcional)
                    </Label>
                    <textarea
                        id="service-description"
                        v-model="draft.description"
                        name="description"
                        rows="3"
                        maxlength="500"
                        placeholder="Qué incluye el servicio, en una línea."
                        class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="service-duration">Duración</Label>
                        <Select v-model="draft.duration_minutes">
                            <SelectTrigger id="service-duration" class="w-full">
                                <SelectValue placeholder="Elegí la duración" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="minutes in durationOptions"
                                    :key="minutes"
                                    :value="String(minutes)"
                                >
                                    {{ formatDuration(minutes) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <input
                            type="hidden"
                            name="duration_minutes"
                            :value="draft.duration_minutes"
                        />
                        <InputError :message="errors.duration_minutes" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="service-price">Precio</Label>
                        <Input
                            id="service-price"
                            v-model="draft.price"
                            name="price"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            placeholder="0"
                        />
                        <InputError :message="errors.price" />
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-md bg-muted/50 p-3">
                    <Checkbox
                        id="service-active"
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
                        <Label for="service-active">Servicio activo</Label>
                        <p class="text-xs text-muted-foreground">
                            Si lo desactivás deja de aparecer en el catálogo
                            público, pero los turnos ya reservados siguen en
                            pie.
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
                        {{ editing ? 'Guardar cambios' : 'Crear servicio' }}
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
                <DialogTitle>
                    ¿Eliminar
                    <template v-if="pendingDelete">
                        “{{ pendingDelete.name }}”
                    </template>
                    ?
                </DialogTitle>
                <DialogDescription>
                    Si el servicio tiene reservas asociadas no se borra: se
                    desactiva, para no arrastrarse los turnos ya vendidos. Si
                    nunca se reservó, se elimina definitivamente.
                </DialogDescription>
            </DialogHeader>

            <div
                v-if="pendingDelete"
                class="flex items-start gap-2 rounded-md bg-muted/50 p-3 text-sm text-muted-foreground"
            >
                <Info class="mt-0.5 size-4 shrink-0" />
                <span>
                    <Scissors class="mr-1 inline size-3.5" />
                    {{ pendingDelete.name }} tiene
                    {{ pendingDelete.active_bookings_count ?? 0 }}
                    reserva(s) activa(s).
                </span>
            </div>

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
