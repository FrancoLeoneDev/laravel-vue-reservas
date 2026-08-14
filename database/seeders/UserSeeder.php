<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Usuarios de demo.
 *
 * Las dos cuentas fijas (admin y cliente) son las que se muestran en el README para
 * entrar sin registrarse. Van con `email_verified_at` seteado para que el middleware
 * `verified` no las frene.
 */
class UserSeeder extends Seeder
{
    /**
     * @var array<int, array{string, string}>
     */
    private const CLIENTES = [
        ['Camila Aguirre', 'camila.aguirre@example.com'],
        ['Lucas Benítez', 'lucas.benitez@example.com'],
        ['Julieta Ferreyra', 'julieta.ferreyra@example.com'],
        ['Nicolás Peralta', 'nicolas.peralta@example.com'],
        ['Agustina Molina', 'agustina.molina@example.com'],
        ['Tomás Cabrera', 'tomas.cabrera@example.com'],
        ['Micaela Ledesma', 'micaela.ledesma@example.com'],
        ['Federico Ávalos', 'federico.avalos@example.com'],
        ['Rocío Maidana', 'rocio.maidana@example.com'],
        ['Santiago Quiroga', 'santiago.quiroga@example.com'],
    ];

    public function run(): void
    {
        $this->crear('Valentina Ríos', 'admin@demo.com', UserRole::Admin, '+54 9 11 4455-6677');
        $this->crear('Martín Sosa', 'cliente@demo.com', UserRole::Client, '+54 9 11 3322-1100');

        foreach (self::CLIENTES as [$name, $email]) {
            $this->crear($name, $email, UserRole::Client, $this->telefono());
        }
    }

    /**
     * Idempotente por email: correr el seeder dos veces no duplica usuarios.
     */
    private function crear(string $name, string $email, UserRole $role, string $phone): void
    {
        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'role' => $role,
                'phone' => $phone,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
    }

    private function telefono(): string
    {
        return sprintf('+54 9 11 %04d-%04d', fake()->numberBetween(2000, 6999), fake()->numberBetween(1000, 9999));
    }
}
