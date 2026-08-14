<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Catálogo de servicios de Nova Studio.
 *
 * Todas las duraciones son múltiplos de 15', que es el paso de la grilla
 * (AvailabilityService::SLOT_STEP_MINUTES). Si alguna no lo fuera, el turno
 * terminaría fuera de la grilla y quedarían huecos imposibles de reservar.
 */
class ServiceSeeder extends Seeder
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private const SERVICIOS = [
        [
            'slug' => 'corte-de-pelo',
            'name' => 'Corte de pelo',
            'description' => 'Corte personalizado con lavado, asesoramiento de estilo y peinado final.',
            'duration_minutes' => 30,
            'price' => 12000,
        ],
        [
            'slug' => 'corte-y-barba',
            'name' => 'Corte + barba',
            'description' => 'Corte de pelo completo más perfilado y arreglo de barba con toalla caliente.',
            'duration_minutes' => 45,
            'price' => 18500,
        ],
        [
            'slug' => 'brushing',
            'name' => 'Brushing',
            'description' => 'Lavado y secado con cepillo para dejar el pelo con volumen y movimiento.',
            'duration_minutes' => 30,
            'price' => 10500,
        ],
        [
            'slug' => 'tratamiento-de-hidratacion',
            'name' => 'Tratamiento de hidratación',
            'description' => 'Baño de crema con keratina y sellado térmico para pelo seco o dañado.',
            'duration_minutes' => 45,
            'price' => 21000,
        ],
        [
            'slug' => 'coloracion',
            'name' => 'Coloración',
            'description' => 'Color completo o retoque de raíz con tintura sin amoníaco, incluye lavado y peinado.',
            'duration_minutes' => 90,
            'price' => 38000,
        ],
        [
            'slug' => 'reflejos-y-mechas',
            'name' => 'Reflejos / mechas',
            'description' => 'Mechas con papel o gorro, matizado y tratamiento post decoloración.',
            'duration_minutes' => 120,
            'price' => 52000,
        ],
    ];

    public function run(): void
    {
        foreach (self::SERVICIOS as $servicio) {
            Service::query()->updateOrCreate(
                ['slug' => $servicio['slug']],
                [
                    'name' => $servicio['name'],
                    'description' => $servicio['description'],
                    'duration_minutes' => $servicio['duration_minutes'],
                    'price' => $servicio['price'],
                    'is_active' => true,
                ],
            );
        }
    }
}
