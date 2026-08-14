<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmada',
            self::Cancelled => 'Cancelada',
            self::Completed => 'Completada',
        };
    }

    /**
     * Estados que ocupan el turno en la agenda.
     *
     * Una reserva cancelada libera el hueco: no bloquea a nadie más y, gracias a la
     * columna generada `slot_key`, tampoco cuenta para el índice único.
     *
     * @return array<int, string>
     */
    public static function blocking(): array
    {
        return [self::Confirmed->value, self::Completed->value];
    }
}
