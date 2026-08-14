<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AvailabilityRequest;
use App\Models\Availability;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    public function index(): Response
    {
        $windows = Availability::query()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Agrupamos por día ordenando de lunes a domingo, que es como se lee una agenda.
        $days = [];

        foreach ([1, 2, 3, 4, 5, 6, 0] as $day) {
            $days[] = [
                'day_of_week' => $day,
                'name' => Availability::DAY_NAMES[$day],
                'windows' => $windows
                    ->where('day_of_week', $day)
                    ->map(fn (Availability $window) => [
                        'id' => $window->id,
                        'start_time' => substr($window->start_time, 0, 5),
                        'end_time' => substr($window->end_time, 0, 5),
                        'is_active' => $window->is_active,
                    ])->values()->all(),
            ];
        }

        return Inertia::render('admin/Availability', [
            'days' => $days,
        ]);
    }

    public function store(AvailabilityRequest $request): RedirectResponse
    {
        Availability::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tramo horario agregado.']);

        return to_route('admin.availability.index');
    }

    public function update(AvailabilityRequest $request, Availability $availability): RedirectResponse
    {
        $availability->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tramo horario actualizado.']);

        return to_route('admin.availability.index');
    }

    /**
     * Borrar un tramo no toca las reservas que ya se vendieron dentro de él.
     *
     * Es intencional: los huecos se calculan hacia adelante, pero un turno ya
     * confirmado es un compromiso con el cliente y sigue apareciendo en la agenda.
     */
    public function destroy(Availability $availability): RedirectResponse
    {
        $availability->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tramo horario eliminado.']);

        return to_route('admin.availability.index');
    }
}
