<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\SlotUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBookingStatusRequest;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class BookingStatusController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
    ) {}

    /**
     * Confirmar, cancelar o completar una reserva desde el panel.
     */
    public function update(UpdateBookingStatusRequest $request, Booking $booking): RedirectResponse
    {
        $this->authorize('manage', Booking::class);

        $status = $request->status();

        try {
            $this->bookings->changeStatus($booking, $status);
        } catch (SlotUnavailableException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Reserva marcada como '.strtolower($status->label()).'.',
        ]);

        return back();
    }
}
