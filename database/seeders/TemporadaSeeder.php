<?php

namespace Database\Seeders;

use App\Enums\TipoAjuste;
use App\Models\Temporada;
use Illuminate\Database\Seeder;

/**
 * Temporadas de ejemplo, DESACTIVADAS a propósito.
 *
 * // DECISIÓN (29-ago-2026): los nombres, fechas y porcentajes de aquí son datos
 * de demo inventados en la fase inicial, no salen de la tarifa del cliente. El
 * catálogo 2026 tiene precios planos, sin recargo por temporada, y el contrato
 * define las temporadas de otra forma (baja = noviembre a abril). Con ellas
 * activas el configurador cobraba un 20 % de más en verano sobre el precio de
 * tarifa, así que se dejan creadas pero apagadas: sirven de plantilla para que
 * el comercio ponga las suyas desde Panel → Temporadas.
 */
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
                    'activo' => false,
                ],
            );
        }
    }
}
