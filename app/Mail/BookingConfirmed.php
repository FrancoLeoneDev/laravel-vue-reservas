<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Route;

/**
 * Mail de confirmación que recibe el cliente al reservar un turno.
 *
 * El armado de los textos (fecha en español, precio, duración) vive acá y no en la
 * vista: la Blade se limita a maquetar lo que ya viene listo.
 */
class BookingConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Booking $booking,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu turno en Nova Studio está confirmado',
        );
    }

    public function content(): Content
    {
        $this->booking->loadMissing(['user', 'service']);

        return new Content(
            markdown: 'emails.booking-confirmed',
            with: [
                'cliente' => $this->booking->user->name,
                'servicio' => $this->booking->service->name,
                'cuando' => $this->fechaLegible(),
                'duracion' => $this->duracionLegible(),
                'precio' => $this->precioLegible(),
                'misReservasUrl' => $this->misReservasUrl(),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * "martes 18 de agosto a las 10:30".
     *
     * translatedFormat() traduce nombres de día y mes según el locale de la app (es).
     * Ojo con los escapes: en el formato de PHP `d` es día con cero a la izquierda y
     * `e` es la zona horaria, así que el "de" literal va como `\d\e`.
     */
    private function fechaLegible(): string
    {
        $inicio = $this->booking->starts_at;

        return $inicio->translatedFormat('l j \d\e F').' a las '.$inicio->format('H:i');
    }

    /**
     * "45 minutos" o "1 h 30 min" cuando pasa de la hora.
     */
    private function duracionLegible(): string
    {
        $minutos = $this->booking->service->duration_minutes;

        if ($minutos < 60) {
            return $minutos.' minutos';
        }

        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        return $resto === 0
            ? $horas.' h'
            : $horas.' h '.$resto.' min';
    }

    /**
     * Precio con formato argentino: $4.500,00
     */
    private function precioLegible(): string
    {
        $precio = (float) $this->booking->service->price;

        return '$'.number_format($precio, 2, ',', '.');
    }

    /**
     * URL de "Mis reservas".
     *
     * Se resuelve por nombre de ruta si existe y, si todavía no está definida, cae al
     * dashboard: un mail nunca debería romperse por una ruta que falta.
     */
    private function misReservasUrl(): string
    {
        foreach (['bookings.index', 'reservas.index', 'dashboard'] as $nombre) {
            if (Route::has($nombre)) {
                return route($nombre);
            }
        }

        return url('/');
    }
}
