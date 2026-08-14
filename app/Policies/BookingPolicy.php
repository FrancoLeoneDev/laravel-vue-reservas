<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * Un cliente sólo ve y toca SUS reservas. El admin ve y toca todas.
 */
class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin() || $booking->user_id === $user->id;
    }

    /**
     * Cancelar la propia reserva: sólo si sigue confirmada y todavía no pasó.
     *
     * El admin puede cancelar cualquiera, incluso una del pasado (por ejemplo, para
     * corregir una carga mal hecha).
     */
    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $booking->user_id === $user->id && $booking->isCancellable();
    }

    /**
     * Cambiar el estado a mano (confirmar / completar / cancelar) es cosa del admin.
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }
}
