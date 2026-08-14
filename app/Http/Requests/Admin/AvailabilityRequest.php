<?php

namespace App\Http\Requests\Admin;

use App\Models\Availability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AvailabilityRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // En el update la ruta trae el modelo; en el store no hay nada que ignorar.
        $availability = $this->route('availability');
        $ignoreId = $availability instanceof Availability ? $availability->id : null;

        return [
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => [
                'required',
                'date_format:H:i',
                // Un mismo día no puede tener dos tramos arrancando a la misma hora.
                Rule::unique('availabilities', 'start_time')
                    ->where('day_of_week', $this->integer('day_of_week'))
                    ->ignore($ignoreId),
            ],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            // El <input type="time"> puede mandar "09:00:00"; lo normalizamos a H:i.
            'start_time' => $this->normalizeTime($this->input('start_time')),
            'end_time' => $this->normalizeTime($this->input('end_time')),
        ]);
    }

    private function normalizeTime(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)
            ? substr($value, 0, 5)
            : $value;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'day_of_week.between' => 'Elegí un día de la semana válido.',
            'start_time.unique' => 'Ya hay un tramo que arranca a esa hora el '.($this->dayName() ?: 'ese día').'.',
            'end_time.after' => 'La hora de cierre tiene que ser posterior a la de apertura.',
        ];
    }

    private function dayName(): string
    {
        return Availability::DAY_NAMES[$this->integer('day_of_week')] ?? '';
    }
}
