<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Se lanza cuando el turno pedido dejó de estar disponible.
 *
 * Puede ocurrir por dos caminos y los dos son legítimos:
 *
 *  1. La revalidación dentro de la transacción encontró el hueco ocupado
 *     (alguien llegó primero mientras el usuario elegía).
 *  2. El INSERT reventó contra el índice único `bookings_active_slot_unique`,
 *     la última línea de defensa a nivel de base de datos.
 */
class SlotUnavailableException extends RuntimeException
{
    public function __construct(
        string $message = 'Ese turno acaba de ser reservado por otra persona. Elegí otro horario.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
