<?php

namespace App\Http\Controllers;

use App\Exceptions\SlotUnavailableException;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Service;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly BookingService $bookings,
    ) {}

    /**
     * Selector de fecha y hora para un servicio.
     *
     * Los huecos que se muestran salen de AvailabilityService, así que son huecos
     * reales: agenda del negocio menos lo que ya está reservado, troceado según la
     * duración de este servicio en particular.
     */
    public function create(Request $request, Service $service): Response
    {
        abort_unless($service->is_active, 404);

        $days = $this->availability->dailySummary($service, CarbonImmutable::today(), 14);

        // La fecha pedida sólo vale si está dentro del rango que ofrecemos; si no,
        // caemos en el primer día que tenga huecos.
        $requested = $request->string('date')->toString();
        $valid = array_column($days, 'date');

        $selectedDate = in_array($requested, $valid, true)
            ? $requested
            : ($this->firstDayWithSlots($days) ?? $valid[0]);

        return Inertia::render('bookings/Create', [
            'service' => $service->only(['id', 'name', 'slug', 'description', 'duration_minutes', 'price']),
            'days' => $days,
            'selectedDate' => $selectedDate,
            'slots' => $this->availability->availableSlots($service, AvailabilityService::parse($selectedDate)),
            'stepMinutes' => AvailabilityService::SLOT_STEP_MINUTES,
        ]);
    }

    /**
     * Confirma la reserva.
     *
     * Notar que acá NO se comprueba disponibilidad: eso pasa dentro de
     * BookingService::book(), en la transacción y con las filas bloqueadas. Hacerlo
     * en el controlador sería la condición de carrera que este proyecto evita.
     */
    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $service = Service::query()->findOrFail($request->integer('service_id'));

        try {
            $this->bookings->book(
                user: $request->user(),
                service: $service,
                startsAt: AvailabilityService::parse($request->string('starts_at')->toString()),
                notes: $request->string('notes')->toString() ?: null,
            );
        } catch (SlotUnavailableException $e) {
            // Volvemos al selector con el error. La grilla se recalcula sola al
            // recargar, así que el usuario ve el hueco ya ocupado y elige otro.
            return back()->withErrors(['starts_at' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => '¡Listo! Tu turno quedó confirmado.']);

        return to_route('bookings.index');
    }

    /**
     * "Mis reservas" del cliente autenticado.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Booking::class);

        $bookings = Booking::query()
            // Eager loading: sin esto, pintar N reservas dispara N consultas al
            // servicio. Es el N+1 clásico de esta pantalla.
            ->with('service:id,name,duration_minutes,price')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('starts_at')
            ->get();

        return Inertia::render('bookings/Index', [
            'upcoming' => $this->present($bookings->filter(fn (Booking $b) => $b->starts_at->isFuture())->sortBy('starts_at')->values()),
            'past' => $this->present($bookings->filter(fn (Booking $b) => ! $b->starts_at->isFuture())->values()),
        ]);
    }

    /**
     * Cancela una reserva propia. La Policy impide tocar las de otro.
     */
    public function destroy(Booking $booking): RedirectResponse
    {
        $this->authorize('cancel', $booking);

        $this->bookings->cancel($booking);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Turno cancelado. El horario quedó libre.']);

        return back();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Booking>  $bookings
     * @return array<int, array<string, mixed>>
     */
    private function present($bookings): array
    {
        return $bookings->map(fn (Booking $booking) => [
            'id' => $booking->id,
            'service' => $booking->service->name,
            'duration_minutes' => $booking->service->duration_minutes,
            'price' => $booking->service->price,
            'starts_at' => $booking->starts_at->toIso8601String(),
            'date_label' => $booking->starts_at->translatedFormat('l j \d\e F'),
            'time_label' => $booking->starts_at->format('H:i').' a '.$booking->ends_at->format('H:i'),
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'can_cancel' => $booking->isCancellable(),
            'notes' => $booking->notes,
        ])->all();
    }

    /**
     * @param  array<int, array{date: string, slots: int}>  $days
     */
    private function firstDayWithSlots(array $days): ?string
    {
        foreach ($days as $day) {
            if ($day['slots'] > 0) {
                return $day['date'];
            }
        }

        return null;
    }
}
