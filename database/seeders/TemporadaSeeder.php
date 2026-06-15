<?php

namespace Database\Seeders;

use App\Enums\TipoAjuste;
use App\Models\Temporada;
use Illuminate\Database\Seeder;

class TemporadaSeeder extends Seeder
{
    public function run(): void
    {
        $temporadas = [
            [
                'nombre' => 'Temporada Alta (verano de bodas)',
                'slug_ref' => 'alta',
                'fecha_inicio' => '2026-06-01',
                'fecha_fin' => '2026-09-30',
                'tipo_ajuste' => TipoAjuste::Porcentaje,
                'valor' => 20,
            ],
            [
                'nombre' => 'Navidad y Fin de Año',
                'slug_ref' => 'navidad',
                'fecha_inicio' => '2026-12-01',
                'fecha_fin' => '2027-01-06',
                'tipo_ajuste' => TipoAjuste::Porcentaje,
                'valor' => 15,
            ],
            [
                'nombre' => 'Temporada Baja (invierno)',
                'slug_ref' => 'baja',
                'fecha_inicio' => '2026-01-15',
                'fecha_fin' => '2026-03-15',
                'tipo_ajuste' => TipoAjuste::Fijo,
                'valor' => -30,
            ],
        ];

        foreach ($temporadas as $temporada) {
            Temporada::updateOrCreate(
                ['nombre' => $temporada['nombre']],
                [
                    'fecha_inicio' => $temporada['fecha_inicio'],
                    'fecha_fin' => $temporada['fecha_fin'],
                    'tipo_ajuste' => $temporada['tipo_ajuste'],
                    'valor' => $temporada['valor'],
                    'activo' => true,
                ],
            );
        }
    }
}
