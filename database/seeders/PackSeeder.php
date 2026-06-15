<?php

namespace Database\Seeders;

use App\Models\Complemento;
use App\Models\Experiencia;
use App\Models\Pack;
use Illuminate\Database\Seeder;

class PackSeeder extends Seeder
{
    public function run(): void
    {
        $packs = [
            [
                'experiencia_slug' => 'fotomaton-clasico',
                'nombre' => 'Pack Boda Esencial',
                'slug' => 'pack-boda-esencial',
                'descripcion' => 'Fotomatón con impresiones ilimitadas, atrezzo básico y libro de firmas. Todo lo imprescindible a precio cerrado.',
                'precio' => 450,
                'complementos' => [
                    'impresiones-ilimitadas' => 1,
                    'atrezzo-basico' => 1,
                    'libro-firmas' => 1,
                ],
            ],
            [
                'experiencia_slug' => 'espejo-magico',
                'nombre' => 'Pack Espejo Premium',
                'slug' => 'pack-espejo-premium',
                'descripcion' => 'Espejo Mágico con impresiones ilimitadas, atrezzo premium, neón personalizado y álbum de recuerdos.',
                'precio' => 650,
                'complementos' => [
                    'impresiones-ilimitadas' => 1,
                    'atrezzo-premium' => 1,
                    'neon-personalizado' => 1,
                    'album-recuerdos' => 1,
                ],
            ],
        ];

        foreach ($packs as $datos) {
            $experiencia = Experiencia::where('slug', $datos['experiencia_slug'])->firstOrFail();

            $pack = Pack::updateOrCreate(
                ['slug' => $datos['slug']],
                [
                    'experiencia_id' => $experiencia->id,
                    'nombre' => $datos['nombre'],
                    'descripcion' => $datos['descripcion'],
                    'precio' => $datos['precio'],
                    'activo' => true,
                ],
            );

            $sync = [];
            foreach ($datos['complementos'] as $complementoSlug => $cantidad) {
                $complemento = Complemento::where('slug', $complementoSlug)->firstOrFail();
                $sync[$complemento->id] = ['cantidad' => $cantidad];
            }

            $pack->complementos()->sync($sync);
        }
    }
}
