<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario administrador del panel Filament (/admin).
        // DECISIÓN: email neutro para el repo; cámbialo en producción.
        User::query()->updateOrCreate(
            ['email' => 'admin@troula.test'],
            ['name' => 'Administración Troula', 'password' => bcrypt('password')],
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
