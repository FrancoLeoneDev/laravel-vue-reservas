<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Envía el mail de confirmación de una reserva.
 *
 * ¿Por qué en cola y no inline dentro de book()?
 *
 *  · Latencia: hablar con el SMTP puede tardar cientos de milisegundos (o segundos si
 *    el proveedor está lento). El usuario no tiene por qué esperar eso mirando un
 *    spinner: la reserva ya está confirmada en base y la respuesta puede volver ya.
 *
 *  · Aislamiento de fallos: si el servidor de mail está caído, un envío inline
 *    lanzaría una excepción DESPUÉS del commit y el usuario vería un error 500 por
 *    una reserva que en realidad se guardó perfecto. En cola, el fallo queda contenido
 *    en el worker, se reintenta solo, y si igual no sale termina en `failed_jobs` para
 *    revisarlo a mano. Un mail que no salió no puede tirar abajo una reserva válida.
 */
class SendBookingConfirmation implements ShouldQueue
{
    use Queueable;

    /**
     * Tres intentos: cubre el caso típico (SMTP intermitente, timeout puntual) sin
     * insistir eternamente contra un proveedor que rechaza la dirección.
     */
    public int $tries = 3;

    public function __construct(
        public readonly Booking $booking,
    ) {}

    /**
     * Espera creciente entre reintentos: 10s, 1min y 5min.
     *
     * Arranca corto porque la mayoría de los fallos de SMTP son transitorios, y se
     * abre para darle tiempo a recuperarse a un proveedor con problemas reales.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(): void
    {
        // SerializesModels guarda solo el ID y recarga el modelo al ejecutar, así que
        // acá tenemos el estado actual de la reserva, no el del momento del dispatch.
        //
        // Entre que se reservó y que el worker levanta el job pudieron pasar minutos
        // (cola con backlog, reintentos con backoff). Si en el medio el cliente canceló,
        // mandar "tu turno está confirmado" sería directamente información falsa:
        // mejor salir sin enviar nada.
        if ($this->booking->status === BookingStatus::Cancelled) {
            return;
        }

        // `user` y `service` son FKs obligatorias con cascadeOnDelete: si el modelo
        // existe, sus relaciones existen. Solo nos aseguramos de tenerlas cargadas.
        $this->booking->loadMissing(['user', 'service']);

        Mail::to($this->booking->user->email, $this->booking->user->name)
            ->send(new BookingConfirmed($this->booking));
    }
}
