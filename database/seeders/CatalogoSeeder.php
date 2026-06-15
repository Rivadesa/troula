<?php

namespace Database\Seeders;

use App\Models\CategoriaComplemento;
use App\Models\Complemento;
use App\Models\Experiencia;
use Illuminate\Database\Seeder;

class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $this->categorias();
        $this->complementos();
        $this->experiencias();
        $this->asociaciones();
    }

    private function categorias(): void
    {
        $categorias = [
            ['nombre' => 'Fondos', 'slug' => 'fondos', 'orden' => 1],
            ['nombre' => 'Atrezzo', 'slug' => 'atrezzo', 'orden' => 2],
            ['nombre' => 'Impresión', 'slug' => 'impresion', 'orden' => 3],
            ['nombre' => 'Neones y decoración', 'slug' => 'neones-decoracion', 'orden' => 4],
            ['nombre' => 'Recuerdos', 'slug' => 'recuerdos', 'orden' => 5],
        ];

        foreach ($categorias as $categoria) {
            CategoriaComplemento::updateOrCreate(['slug' => $categoria['slug']], $categoria);
        }
    }

    private function complementos(): void
    {
        $porCategoria = [
            'fondos' => [
                ['nombre' => 'Fondo de lentejuelas', 'slug' => 'fondo-lentejuelas', 'precio' => 60],
                ['nombre' => 'Fondo floral', 'slug' => 'fondo-floral', 'precio' => 50],
                ['nombre' => 'Fondo personalizado', 'slug' => 'fondo-personalizado', 'precio' => 90],
            ],
            'atrezzo' => [
                ['nombre' => 'Pack de atrezzo básico', 'slug' => 'atrezzo-basico', 'precio' => 30],
                ['nombre' => 'Pack de atrezzo premium', 'slug' => 'atrezzo-premium', 'precio' => 55],
            ],
            'impresion' => [
                ['nombre' => 'Impresiones ilimitadas', 'slug' => 'impresiones-ilimitadas', 'precio' => 80],
                ['nombre' => 'Tira de fotos extra', 'slug' => 'tira-fotos-extra', 'precio' => 25],
            ],
            'neones-decoracion' => [
                ['nombre' => 'Neón personalizado', 'slug' => 'neon-personalizado', 'precio' => 70],
                ['nombre' => 'Alfombra roja', 'slug' => 'alfombra-roja', 'precio' => 35],
            ],
            'recuerdos' => [
                ['nombre' => 'Libro de firmas', 'slug' => 'libro-firmas', 'precio' => 45],
                ['nombre' => 'Álbum de recuerdos', 'slug' => 'album-recuerdos', 'precio' => 65],
            ],
        ];

        foreach ($porCategoria as $slugCategoria => $items) {
            $categoria = CategoriaComplemento::where('slug', $slugCategoria)->firstOrFail();

            foreach ($items as $item) {
                Complemento::updateOrCreate(
                    ['slug' => $item['slug']],
                    [...$item, 'categoria_id' => $categoria->id, 'activo' => true],
                );
            }
        }
    }

    private function experiencias(): void
    {
        $experiencias = [
            [
                'nombre' => 'Fotomatón Clásico',
                'slug' => 'fotomaton-clasico',
                'descripcion' => 'El fotomatón de toda la vida con impresión instantánea. Ideal para bodas y banquetes: tus invitados se llevan un recuerdo en papel al momento.',
                'precio_base' => 350,
                'unidades' => 2,
                'permite_turnos' => true,
                'orden' => 1,
            ],
            [
                'nombre' => 'Espejo Mágico',
                'slug' => 'espejo-magico',
                'descripcion' => 'Un espejo interactivo de cuerpo entero con animaciones, firma táctil y marcos personalizados. La experiencia más espectacular para tu evento.',
                'precio_base' => 450,
                'unidades' => 1,
                'permite_turnos' => true,
                'orden' => 2,
            ],
            [
                'nombre' => 'Photocall con Sofá Decorado',
                'slug' => 'photocall-sofa-decorado',
                'descripcion' => 'Rincón fotográfico con sofá vintage, atrezzo y decoración floral. Perfecto para fotos de grupo durante todo el evento.',
                'precio_base' => 250,
                'unidades' => 1,
                'permite_turnos' => false,
                'orden' => 3,
            ],
            [
                'nombre' => 'Cabina 360',
                'slug' => 'cabina-360',
                'descripcion' => 'Plataforma giratoria que graba vídeos 360º en cámara lenta. El must de las bodas modernas, listo para compartir en redes.',
                'precio_base' => 550,
                'unidades' => 1,
                'permite_turnos' => false,
                'orden' => 4,
            ],
        ];

        foreach ($experiencias as $experiencia) {
            Experiencia::updateOrCreate(['slug' => $experiencia['slug']], [...$experiencia, 'activo' => true]);
        }
    }

    /**
     * Asocia a cada experiencia SOLO los complementos que le corresponden, con
     * sus reglas (obligatorio, override de precio, cantidad máxima, orden).
     */
    private function asociaciones(): void
    {
        // experiencia_slug => [complemento_slug => [reglas pivote]]
        $mapa = [
            'fotomaton-clasico' => [
                'impresiones-ilimitadas' => ['obligatorio' => true, 'orden' => 1],
                'tira-fotos-extra' => ['cantidad_maxima' => 5, 'orden' => 2],
                'fondo-lentejuelas' => ['orden' => 3],
                'fondo-floral' => ['orden' => 4],
                'atrezzo-basico' => ['orden' => 5],
                'atrezzo-premium' => ['orden' => 6],
                'libro-firmas' => ['orden' => 7],
            ],
            'espejo-magico' => [
                'impresiones-ilimitadas' => ['obligatorio' => true, 'precio_override' => 70, 'orden' => 1],
                'fondo-personalizado' => ['orden' => 2],
                'atrezzo-premium' => ['orden' => 3],
                'neon-personalizado' => ['orden' => 4],
                'alfombra-roja' => ['orden' => 5],
                'album-recuerdos' => ['orden' => 6],
            ],
            'photocall-sofa-decorado' => [
                'fondo-floral' => ['orden' => 1],
                'atrezzo-basico' => ['orden' => 2],
                'neon-personalizado' => ['precio_override' => 60, 'orden' => 3],
                'alfombra-roja' => ['orden' => 4],
            ],
            'cabina-360' => [
                'atrezzo-premium' => ['orden' => 1],
                'neon-personalizado' => ['orden' => 2],
                'libro-firmas' => ['orden' => 3],
                'album-recuerdos' => ['orden' => 4],
            ],
        ];

        foreach ($mapa as $experienciaSlug => $complementos) {
            $experiencia = Experiencia::where('slug', $experienciaSlug)->firstOrFail();

            $sync = [];
            foreach ($complementos as $complementoSlug => $reglas) {
                $complemento = Complemento::where('slug', $complementoSlug)->firstOrFail();
                $sync[$complemento->id] = [
                    'precio_override' => $reglas['precio_override'] ?? null,
                    'obligatorio' => $reglas['obligatorio'] ?? false,
                    'cantidad_maxima' => $reglas['cantidad_maxima'] ?? 1,
                    'orden' => $reglas['orden'] ?? 0,
                ];
            }

            $experiencia->complementos()->sync($sync);
        }
    }
}
