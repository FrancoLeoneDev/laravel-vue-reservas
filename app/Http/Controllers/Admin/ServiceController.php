<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use App\Services\AvailabilityService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Services', [
            // withCount evita traer las reservas sólo para contarlas: una consulta con
            // subquery en vez de una por servicio.
            'services' => Service::query()
                ->withCount(['bookings as active_bookings_count' => fn ($query) => $query->whereIn('status', BookingStatus::blocking())])
                ->orderBy('name')
                ->get(),
            'stepMinutes' => AvailabilityService::SLOT_STEP_MINUTES,
        ]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        Service::create([
            ...$request->validated(),
            'slug' => $request->slug(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Servicio creado.']);

        return to_route('admin.services.index');
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update([
            ...$request->validated(),
            'slug' => $request->slug(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Servicio actualizado.']);

        return to_route('admin.services.index');
    }

    /**
     * Los servicios con reservas activas no se borran: se desactivan.
     *
     * Borrarlos arrastraría por cascade los turnos ya vendidos y el negocio perdería
     * el histórico. Desactivar los saca del catálogo público sin tocar el pasado.
     */
    public function destroy(Service $service): RedirectResponse
    {
        $hasBookings = $service->bookings()->exists();

        if ($hasBookings) {
            $service->update(['is_active' => false]);

            Inertia::flash('toast', [
                'type' => 'info',
                'message' => 'El servicio tiene reservas asociadas, así que se desactivó en lugar de borrarse.',
            ]);

            return to_route('admin.services.index');
        }

        $service->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Servicio eliminado.']);

        return to_route('admin.services.index');
    }
}
