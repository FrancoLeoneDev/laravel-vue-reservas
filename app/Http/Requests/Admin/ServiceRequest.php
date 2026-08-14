<?php

namespace App\Http\Requests\Admin;

use App\Models\Service;
use App\Services\AvailabilityService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ServiceRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            // Múltiplo del paso de la grilla: si un servicio durara 37 minutos, los
            // turnos siguientes quedarían desalineados y la agenda se fragmentaría.
            'duration_minutes' => [
                'required',
                'integer',
                'min:'.AvailabilityService::SLOT_STEP_MINUTES,
                'max:480',
                'multiple_of:'.AvailabilityService::SLOT_STEP_MINUTES,
            ],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * El slug se deriva del nombre y se garantiza único a mano, porque la ruta
     * pública `/reservar/{service:slug}` lo usa como identificador.
     */
    public function slug(): string
    {
        $base = Str::slug($this->string('name')->toString());
        $slug = $base;
        $i = 2;

        $ignoreId = $this->route('service')?->id;

        while ($this->slugTaken($slug, $ignoreId)) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function slugTaken(string $slug, ?int $ignoreId): bool
    {
        return Service::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Poné un nombre para el servicio.',
            'duration_minutes.multiple_of' => 'La duración tiene que ser múltiplo de '.AvailabilityService::SLOT_STEP_MINUTES.' minutos.',
            'duration_minutes.min' => 'La duración mínima es de '.AvailabilityService::SLOT_STEP_MINUTES.' minutos.',
            'price.required' => 'Poné un precio.',
            'price.numeric' => 'El precio tiene que ser un número.',
        ];
    }
}
