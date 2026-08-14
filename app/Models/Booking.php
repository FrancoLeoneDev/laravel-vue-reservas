<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $service_id
 * @property int $user_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property BookingStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Service $service
 * @property-read User $user
 */
#[Fillable(['service_id', 'user_id', 'starts_at', 'ends_at', 'status', 'notes'])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * `slot_key` la calcula MySQL (columna generada). Nunca se escribe desde PHP.
     *
     * @var array<int, string>
     */
    protected $guarded = ['slot_key'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => BookingStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Reservas que efectivamente ocupan la agenda (confirmadas o completadas).
     *
     * @param  Builder<Booking>  $query
     */
    public function scopeBlocking(Builder $query): void
    {
        $query->whereIn('status', BookingStatus::blocking());
    }

    /**
     * Reservas que se solapan con el rango [$startsAt, $endsAt).
     *
     * El solapamiento de dos intervalos medio abiertos es `a.start < b.end && a.end > b.start`.
     * Los extremos que se tocan (una termina 10:30 y la otra empieza 10:30) NO se solapan.
     *
     * @param  Builder<Booking>  $query
     */
    public function scopeOverlapping(Builder $query, Carbon $startsAt, Carbon $endsAt): void
    {
        $query->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);
    }

    public function isCancellable(): bool
    {
        return $this->status === BookingStatus::Confirmed && $this->starts_at->isFuture();
    }
}
