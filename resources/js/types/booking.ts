export type BookingStatus = 'confirmed' | 'cancelled' | 'completed';

export type Service = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    duration_minutes: number;
    price: string;
    is_active?: boolean;
    active_bookings_count?: number;
};

/** Un hueco reservable, calculado en el backend por AvailabilityService. */
export type Slot = {
    /** Formato 'Y-m-d H:i:s': es lo que se manda tal cual al crear la reserva. */
    starts_at: string;
    ends_at: string;
    /** 'HH:mm' para mostrar. */
    label: string;
};

/** Resumen de un día en el selector de fecha. */
export type DaySummary = {
    date: string;
    label: string;
    weekday: string;
    slots: number;
};

/** Reserva tal como la ve el cliente en "Mis reservas". */
export type ClientBooking = {
    id: number;
    service: string;
    duration_minutes: number;
    price: string;
    starts_at: string;
    date_label: string;
    time_label: string;
    status: BookingStatus;
    status_label: string;
    can_cancel: boolean;
    notes: string | null;
};

/** Reserva tal como la ve el admin en la agenda. */
export type AgendaBooking = {
    id: number;
    starts_at: string;
    ends_at: string;
    service: string;
    price: string;
    client: string;
    email: string;
    phone: string | null;
    status: BookingStatus;
    status_label: string;
    notes: string | null;
};

export type AgendaDay = {
    date: string;
    label: string;
    is_today: boolean;
    is_open: boolean;
    bookings: AgendaBooking[];
};

export type AgendaStats = {
    total: number;
    confirmed: number;
    completed: number;
    cancelled: number;
    revenue: number;
    minutes: number;
};

export type AvailabilityWindow = {
    id: number;
    start_time: string;
    end_time: string;
    is_active: boolean;
};

export type AvailabilityDay = {
    day_of_week: number;
    name: string;
    windows: AvailabilityWindow[];
};

export type ScheduleDay = {
    day: string;
    ranges: string[];
};
