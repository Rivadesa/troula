<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

/**
 * Marca de la empresa para la instalación de Retrátate Eventos.
 *
 * // DECISIÓN: solo rellena campos VACÍOS o que aún tienen el valor de demo
 * ("Troula Eventos"). Nunca pisa datos que el cliente haya escrito desde el panel,
 * porque en producción la configuración de empresa/correo/privacidad es suya.
 */
class BrandingRetratateSeeder extends Seeder
{
    public function run(): void
    {
        $configuracion = Configuracion::actual();

        $valores = [
            'nombre' => 'Retrátate Eventos',
            'eslogan' => 'Fotomatones y experiencias para tu evento',
            'web' => 'https://www.retratate.es',
        ];

        $cambios = [];

        foreach ($valores as $campo => $valor) {
            $actual = $configuracion->{$campo};

            if (blank($actual) || $actual === 'Troula Eventos') {
                $cambios[$campo] = $valor;
            }
        }

        if ($cambios === []) {
            $this->command?->info('Branding: la configuración ya está personalizada, no se toca.');

            return;
        }

        $configuracion->update($cambios);

        $this->command?->info('Branding actualizado: '.implode(', ', array_keys($cambios)).'.');
    }
}
