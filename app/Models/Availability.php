<?php

namespace App\Models;

use Database\Factories\AvailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Tramo horario en el que el negocio atiende, para un día de la semana.
 *
 * No es una tabla de turnos: es la agenda "en bruto". Los huecos reservables se
 * calculan en AvailabilityService a partir de estos tramos, restando las reservas
 * que ya existen y troceando según la duración del servicio elegido.
 *
 * @property int $id
 * @property int $day_of_week 0 = domingo ... 6 = sábado
 * @property string $start_time
 * @property string $end_time
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['day_of_week', 'start_time', 'end_time', 'is_active'])]
class Availability extends Model
{
    /** @use HasFactory<AvailabilityFactory> */
    use HasFactory;

    protected $table = 'availabilities';

    /**
     * @var array<int, string>
     */
    public const DAY_NAMES = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function dayName(): string
    {
        return self::DAY_NAMES[$this->day_of_week] ?? '';
    }

    /**
     * @param  Builder<Availability>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
