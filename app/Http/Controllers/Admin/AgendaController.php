<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Availability;
use App\Models\Booking;
use App\Services\AvailabilityService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class AgendaController extends Controller
{
    /**
     * Agenda del día y de la semana.
     *
     * Las dos vistas se resuelven con UNA sola consulta al rango correspondiente,
     * con `service` y `user` cargados de antemano. Sin ese eager loading, una semana
     * con 40 turnos dispararía 80 consultas extra al pintar la tabla (N+1).
     */
    public function index(Request $request): Response
    {
        $view = $request->string('view')->toString() === 'week' ? 'week' : 'day';

        $date = $this->resolveDate($request->string('date')->toString());

        [$from, $to] = $view === 'week'
            ? [$date->startOfWeek(CarbonImmutable::MONDAY), $date->endOfWeek(CarbonImmutable::SUNDAY)]
            : [$date->startOfDay(), $date->endOfDay()];

        $bookings = Booking::query()
            ->with(['service:id,name,duration_minutes,price', 'user:id,name,email,phone'])
            ->whereBetween('starts_at', [$from, $to])
            ->orderBy('starts_at')
            ->get();

        return Inertia::render('admin/Agenda', [
            'view' => $view,
            'date' => $date->format('Y-m-d'),
            'rangeLabel' => $view === 'week'
                ? $from->translatedFormat('j M').' — '.$to->translatedFormat('j M Y')
                : $date->translatedFormat('l j \d\e F \d\e Y'),
            'previousDate' => $view === 'week' ? $date->subWeek()->format('Y-m-d') : $date->subDay()->format('Y-m-d'),
            'nextDate' => $view === 'week' ? $date->addWeek()->format('Y-m-d') : $date->addDay()->format('Y-m-d'),
            'today' => CarbonImmutable::today()->format('Y-m-d'),
            'days' => $this->groupByDay($bookings, $from, $to, $view),
            'stats' => $this->stats($bookings),
            'statuses' => $this->statusOptions(),
        ]);
    }

    /**
     * Agrupa las reservas por día para que el frontend no tenga que hacerlo.
     *
     * @param  EloquentCollection<int, Booking>  $bookings
     * @return array<int, array<string, mixed>>
     */
    private function groupByDay(EloquentCollection $bookings, CarbonImmutable $from, CarbonImmutable $to, string $view): array
    {
        $byDay = $bookings->groupBy(fn (Booking $booking) => $booking->starts_at->format('Y-m-d'));

        $openDays = Availability::query()
            ->active()
            ->pluck('day_of_week')
            ->unique()
            ->all();

        $days = [];
        $cursor = $from->startOfDay();

        while ($cursor->lessThanOrEqualTo($to)) {
            $key = $cursor->format('Y-m-d');

            $days[] = [
                'date' => $key,
                'label' => $cursor->translatedFormat($view === 'week' ? 'l j' : 'l j \d\e F'),
                'is_today' => $cursor->isToday(),
                'is_open' => in_array($cursor->dayOfWeek, $openDays, true),
                'bookings' => ($byDay->get($key) ?? collect())
                    ->map(fn (Booking $booking) => [
                        'id' => $booking->id,
                        'starts_at' => $booking->starts_at->format('H:i'),
                        'ends_at' => $booking->ends_at->format('H:i'),
                        'service' => $booking->service->name,
                        'price' => $booking->service->price,
                        'client' => $booking->user->name,
                        'email' => $booking->user->email,
                        'phone' => $booking->user->phone,
                        'status' => $booking->status->value,
                        'status_label' => $booking->status->label(),
                        'notes' => $booking->notes,
                    ])->values()->all(),
            ];

            $cursor = $cursor->addDay();
        }

        return $days;
    }

    /**
     * @param  EloquentCollection<int, Booking>  $bookings
     * @return array<string, mixed>
     */
    private function stats(EloquentCollection $bookings): array
    {
        $blocking = $bookings->filter(fn (Booking $b) => in_array($b->status->value, BookingStatus::blocking(), true));

        return [
            'total' => $bookings->count(),
            'confirmed' => $bookings->where('status', BookingStatus::Confirmed)->count(),
            'completed' => $bookings->where('status', BookingStatus::Completed)->count(),
            'cancelled' => $bookings->where('status', BookingStatus::Cancelled)->count(),
            'revenue' => (float) $blocking->sum(fn (Booking $b) => (float) $b->service->price),
            'minutes' => (int) $blocking->sum(fn (Booking $b) => $b->service->duration_minutes),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (BookingStatus $status) => ['value' => $status->value, 'label' => $status->label()],
            BookingStatus::cases(),
        );
    }

    private function resolveDate(string $value): CarbonImmutable
    {
        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return CarbonImmutable::today();
        }

        return AvailabilityService::parse($value)->startOfDay();
    }
}
