<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\Service;
use Inertia\Inertia;
use Inertia\Response;

class ServiceCatalogController extends Controller
{
    /**
     * Listado público de servicios.
     */
    public function index(): Response
    {
        return Inertia::render('services/Index', [
            'services' => Service::query()
                ->active()
                ->orderBy('duration_minutes')
                ->get(['id', 'name', 'slug', 'description', 'duration_minutes', 'price']),

            'schedule' => $this->weeklySchedule(),
        ]);
    }

    /**
     * Horarios de atención agrupados por día, para mostrarlos en el listado.
     *
     * @return array<int, array{day: string, ranges: array<int, string>}>
     */
    private function weeklySchedule(): array
    {
        $windows = Availability::query()
            ->active()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get(['day_of_week', 'start_time', 'end_time'])
            ->groupBy('day_of_week');

        $schedule = [];

        // Arrancamos en lunes (1) y dejamos el domingo (0) al final, como se lee una agenda.
        foreach ([1, 2, 3, 4, 5, 6, 0] as $day) {
            $ranges = $windows->get($day);

            if ($ranges === null) {
                continue;
            }

            $schedule[] = [
                'day' => Availability::DAY_NAMES[$day],
                'ranges' => $ranges
                    ->map(fn (Availability $window) => substr($window->start_time, 0, 5).' a '.substr($window->end_time, 0, 5))
                    ->all(),
            ];
        }

        return $schedule;
    }
}
