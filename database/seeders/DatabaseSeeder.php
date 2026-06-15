<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario administrador del panel Filament (/admin).
        // DECISIÓN: emails neutros para el repo; cámbialos en producción.
        User::query()->updateOrCreate(
            ['email' => 'admin@troula.test'],
            ['name' => 'Administración Troula', 'rol' => Rol::Admin, 'password' => bcrypt('password')],
        );

        // Usuario empleado de ejemplo (solo ve reservas y calendario).
        User::query()->updateOrCreate(
            ['email' => 'empleado@troula.test'],
            ['name' => 'Empleado Troula', 'rol' => Rol::Empleado, 'password' => bcrypt('password')],
        );

        $this->call([
            CatalogoSeeder::class,
            PackSeeder::class,
            TemporadaSeeder::class,
            ZonaPorteSeeder::class,
            ReservaSeeder::class,
        ]);
    }
}
