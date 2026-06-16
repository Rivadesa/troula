<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuarios demo SOLO fuera de producción: evita crear cuentas con contraseña
        // conocida o resetear contraseñas reales si alguien ejecuta db:seed en el servidor.
        // En producción, crea el admin con `php artisan make:filament-user`.
        if (! app()->isProduction()) {
            User::query()->updateOrCreate(
                ['email' => 'admin@troula.test'],
                ['name' => 'Administración Troula', 'rol' => Rol::Admin, 'password' => bcrypt('password')],
            );

            User::query()->updateOrCreate(
                ['email' => 'empleado@troula.test'],
                ['name' => 'Empleado Troula', 'rol' => Rol::Empleado, 'password' => bcrypt('password')],
            );
        }

        $this->call([
            CatalogoSeeder::class,
            PackSeeder::class,
            TemporadaSeeder::class,
            ZonaPorteSeeder::class,
            ReservaSeeder::class,
        ]);
    }
}
