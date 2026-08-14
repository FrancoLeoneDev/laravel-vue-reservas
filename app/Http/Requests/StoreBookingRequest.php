<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_id' => [
                'required',
                'integer',
                // El servicio tiene que existir Y estar activo: no se reservan servicios dados de baja.
                Rule::exists('services', 'id')->where('is_active', true),
            ],
            // Sólo se valida el formato. Que el horario esté REALMENTE libre no se
            // decide acá: se revalida dentro de la transacción con las filas bloqueadas
            // (ver BookingService::book). Validarlo en el Request sería exactamente la
            // condición de carrera que este proyecto evita.
            'starts_at' => ['required', 'date_format:Y-m-d H:i:s', 'after:now'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'service_id.required' => 'Elegí un servicio.',
            'service_id.exists' => 'Ese servicio no está disponible.',
            'starts_at.required' => 'Elegí un horario.',
            'starts_at.date_format' => 'El horario elegido no es válido.',
            'starts_at.after' => 'No se puede reservar un turno que ya pasó.',
            'notes.max' => 'El comentario no puede superar los 500 caracteres.',
        ];
    }
}
