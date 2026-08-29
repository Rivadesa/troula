<?php

namespace Database\Seeders;

use App\Models\CategoriaComplemento;
use App\Models\Complemento;
use App\Models\Experiencia;
use App\Models\Pack;
use Illuminate\Database\Seeder;

/**
 * Catálogo REAL de Retrátate Eventos (tarifa 2026, IVA incluido).
 *
 * Sustituye a los seeders de demo `CatalogoSeeder` y `PackSeeder`.
 * Es idempotente por slug: se puede reejecutar para reponer o corregir precios
 * sin duplicar filas (respeta lo que el admin haya creado aparte).
 *
 * // DECISIÓN: los precios que en el PDF van por tramos de cantidad (portafoto imán,
 * invitaciones de madera) y los puestos de comida entran como `a_consultar`: se
 * muestran en el configurador pero no suman al total.
 */
class CatalogoRetratateSeeder extends Seeder
{
    /**
     * Servicio estándar incluido en TODAS las máquinas (va al final de cada descripción).
     */
    private const ESTANDAR = "\n\nIncluye siempre: álbum de firmas personalizado, 3 h de animación, "
        .'copias y fotos ilimitadas, boomerangs y GIFs, QR de descarga, pantalla de inicio personalizable, '
        .'galería web privada, técnico durante todo el evento, seguro de responsabilidad civil, montaje y '
        .'desmontaje, atrezzo y disfraces, formato tira y postal, personalización del diseño y opción de '
        .'video-dedicatorias de 30 s.';

    public function run(): void
    {
        $this->categorias();
        $this->complementos();
        $this->experiencias();
        $this->asociaciones();
        $this->packs();

        // Después de asociaciones(), que hace sync() y borraría lo que este añade.
        $this->call(VariantesRetratateSeeder::class);

        $this->command?->info('Catálogo Retrátate: '
            .Experiencia::count().' experiencias, '
            .Complemento::count().' complementos, '
            .Pack::count().' packs.');
    }

    private function categorias(): void
    {
        $categorias = [
            ['nombre' => 'Decoración', 'slug' => 'decoracion', 'orden' => 1],
            ['nombre' => 'Recuerdos', 'slug' => 'recuerdos', 'orden' => 2],
            ['nombre' => 'Animación', 'slug' => 'animacion', 'orden' => 3],
            ['nombre' => 'Dulces y comida', 'slug' => 'dulces-comida', 'orden' => 4],
            ['nombre' => 'Juegos', 'slug' => 'juegos', 'orden' => 5],
            ['nombre' => 'Detalles para invitados', 'slug' => 'detalles', 'orden' => 6],
        ];

        foreach ($categorias as $categoria) {
            CategoriaComplemento::updateOrCreate(['slug' => $categoria['slug']], $categoria);
        }
    }

    /**
     * Complementos por categoría. `a_consultar` = precio variable, no suma.
     */
    private function complementos(): void
    {
        $porCategoria = [
            'decoracion' => [
                ['nombre' => 'Estructura decorada', 'slug' => 'estructura', 'precio' => 100],
                ['nombre' => 'Fondo Jardín', 'slug' => 'fondo-jardin', 'precio' => 150],
                ['nombre' => 'Shimmer Wall', 'slug' => 'shimmer-wall', 'precio' => 150],
                ['nombre' => 'Neón', 'slug' => 'neon', 'precio' => 80],
                ['nombre' => 'Sofá', 'slug' => 'sofa', 'precio' => 100],
                ['nombre' => 'Alfombra', 'slug' => 'alfombra', 'precio' => 25],
            ],
            'recuerdos' => [
                ['nombre' => 'Audiolibro de firmas', 'slug' => 'audiolibro-firmas', 'precio' => 90],
                ['nombre' => 'Audiolibro de firmas + Vídeo', 'slug' => 'audiolibro-video', 'precio' => 200],
                ['nombre' => 'Letras de madera · Iniciales', 'slug' => 'letras-iniciales', 'precio' => 270],
                ['nombre' => 'Letras de madera · LOVE', 'slug' => 'letras-love', 'precio' => 270],
                ['nombre' => 'Pack Letras (Iniciales + LOVE)', 'slug' => 'pack-letras', 'precio' => 370],
            ],
            'animacion' => [
                [
                    'nombre' => 'Glitter Corner 3 h · 1 maquilladora',
                    'slug' => 'glitter-corner-1',
                    'precio' => 380,
                    'descripcion' => 'Barra de purpurina con maquilladora profesional durante 3 h. Hora extra: 50 € por maquilladora (a concretar).',
                ],
                [
                    'nombre' => 'Glitter Corner 3 h · 2 maquilladoras',
                    'slug' => 'glitter-corner-2',
                    'precio' => 530,
                    'descripcion' => 'Barra de purpurina con dos maquilladoras durante 3 h. Hora extra: 50 € por maquilladora (a concretar).',
                ],
                ['nombre' => 'Hora Loca · Pack A', 'slug' => 'hora-loca-a', 'precio' => 900],
                ['nombre' => 'Hora Loca · Pack B', 'slug' => 'hora-loca-b', 'precio' => 1100],
                ['nombre' => 'Hora Loca · Pack C', 'slug' => 'hora-loca-c', 'precio' => 1350],
                ['nombre' => '100 jeringas shot', 'slug' => 'jeringas-shot', 'precio' => 50],
                ['nombre' => 'Inflable temático', 'slug' => 'inflable-tematico', 'precio' => 40],
                ['nombre' => 'Animador extra', 'slug' => 'animador-extra', 'precio' => 150],
                ['nombre' => '2 pistolas LED + CO₂', 'slug' => 'pistolas-led-co2', 'precio' => 400],
            ],
            'dulces-comida' => [
                ['nombre' => 'Mesa Dulce · solo golosinas', 'slug' => 'mesa-dulce-golosinas', 'precio' => 200],
                ['nombre' => 'Mesa Dulce · con 40 donuts', 'slug' => 'mesa-dulce-donuts', 'precio' => 280],
                ['nombre' => 'Kiosko Moita Troula', 'slug' => 'kiosko-moita-troula', 'precio' => 400],
                ['nombre' => 'Puesto de palomitas', 'slug' => 'puesto-palomitero', 'a_consultar' => true],
                ['nombre' => 'Puesto de algodón de azúcar', 'slug' => 'puesto-algodonero', 'a_consultar' => true],
                ['nombre' => 'Puesto de hot-dogs', 'slug' => 'puesto-hot-dog', 'a_consultar' => true],
                ['nombre' => 'Puesto de gofres', 'slug' => 'puesto-gofres', 'a_consultar' => true],
                ['nombre' => 'Puesto de crepes', 'slug' => 'puesto-crepes', 'a_consultar' => true],
                ['nombre' => 'Puesto de helados', 'slug' => 'puesto-helados', 'a_consultar' => true],
                ['nombre' => 'Puesto de hamburguesas', 'slug' => 'puesto-burguers', 'a_consultar' => true],
            ],
            'juegos' => [
                ['nombre' => 'Futbolín', 'slug' => 'futbolin', 'precio' => 140, 'descripcion' => 'Precio por día.'],
                ['nombre' => 'Máquina Arcade', 'slug' => 'arcade', 'precio' => 110, 'descripcion' => 'Precio por día.'],
            ],
            'detalles' => [
                [
                    'nombre' => 'Portafoto imán',
                    'slug' => 'portafoto-iman',
                    'a_consultar' => true,
                    'descripcion' => 'Precio por unidad según cantidad (4,40 € / 4,00 € / 3,60 €). Te lo presupuestamos.',
                ],
                [
                    'nombre' => 'Invitaciones de madera',
                    'slug' => 'invitaciones-madera',
                    'a_consultar' => true,
                    'descripcion' => 'Precio por unidad según cantidad (5,20 € / 4,80 € / 4,40 €). Te lo presupuestamos.',
                ],
            ],
        ];

        foreach ($porCategoria as $slugCategoria => $items) {
            $categoria = CategoriaComplemento::where('slug', $slugCategoria)->firstOrFail();

            foreach ($items as $item) {
                Complemento::updateOrCreate(['slug' => $item['slug']], [
                    'nombre' => $item['nombre'],
                    'descripcion' => $item['descripcion'] ?? null,
                    'precio' => $item['precio'] ?? 0,
                    'a_consultar' => $item['a_consultar'] ?? false,
                    'categoria_id' => $categoria->id,
                    'activo' => true,
                ]);
            }
        }
    }

    /**
     * Máquinas. El precio corresponde a la duración incluida (3 h salvo nota).
     *
     * // DECISIÓN: solo el Fotomatón Solo trae precio de hora extra en la tarifa (70 €).
     * En el resto queda vacío (sin horas extra en el configurador) hasta que el
     * cliente indique su precio; se rellena desde el panel sin tocar código.
     */
    private function experiencias(): void
    {
        $experiencias = [
            [
                'nombre' => 'Fotomatón Solo',
                'slug' => 'fotomaton-solo',
                'descripcion' => 'Nuestro fotomatón clásico, sin decoración añadida. La opción más versátil: cabe en cualquier rincón y funciona toda la noche.',
                'precio_base' => 450,
                'precio_hora_extra' => 70,
                'unidades' => 2,
                'permite_turnos' => false,
                'orden' => 1,
            ],
            [
                'nombre' => 'Fotomatón con Photocall de Tela',
                'slug' => 'fotomaton-photocall-tela',
                'descripcion' => 'Fotomatón con fondo de tela de 2x2 m o 2,2x2,2 m a juego con tu evento.',
                'precio_base' => 480,
                'unidades' => 1,
                'permite_turnos' => false,
                'orden' => 2,
            ],
            [
                'nombre' => 'Fotomatón con Photocall de Lentejuelas',
                'slug' => 'fotomaton-photocall-lentejuelas',
                'descripcion' => 'Fotomatón con photocall de lentejuelas de 2,4x2,4 m: brillo asegurado en todas las fotos.',
                'precio_base' => 500,
                'unidades' => 1,
                'permite_turnos' => false,
                'orden' => 3,
            ],
            [
                'nombre' => 'Fotomatón con Estructura y Neón',
                'slug' => 'fotomaton-estructura-neon',
                'descripcion' => 'Fotomatón con estructura decorada y neón a elegir. Puedes añadir el sofá para completar el rincón.',
                'precio_base' => 600,
                'unidades' => 1,
                'permite_turnos' => false,
                'orden' => 4,
            ],
            [
                'nombre' => 'Fotomatón Beauty Glam',
                'slug' => 'fotomaton-beauty-glam',
                'descripcion' => 'Fotomatón con iluminación beauty: retratos favorecedores estilo estudio.',
                'precio_base' => 480,
                'unidades' => 1,
                'permite_turnos' => false,
                'orden' => 5,
            ],
            [
                'nombre' => 'Fotomatón Beauty Glam Premium',
                'slug' => 'fotomaton-beauty-glam-premium',
                'descripcion' => 'La versión premium del Beauty Glam, con acabado y decorado de gama alta.',
                'precio_base' => 600,
                'unidades' => 1,
                'permite_turnos' => false,
                'orden' => 6,
            ],
            [
                'nombre' => 'Espejo Mágico',
                'slug' => 'espejo-magico',
                'descripcion' => 'Espejo interactivo de cuerpo entero con animaciones, firma táctil y marcos personalizados.',
                'precio_base' => 530,
                'unidades' => 1,
                'permite_turnos' => false,
                'orden' => 7,
            ],
            [
                'nombre' => 'Plataforma 360º',
                'slug' => 'plataforma-360',
                'descripcion' => 'Plataforma giratoria que graba vídeos 360º a cámara lenta, listos para compartir.',
                'precio_base' => 500,
                'unidades' => 1,
                'permite_turnos' => false,
                'orden' => 8,
            ],
            [
                'nombre' => 'Aéreo 360º',
                'slug' => 'aereo-360',
                'descripcion' => 'Brazo aéreo que graba desde arriba a grupos de hasta 15 personas. El más espectacular.',
                'precio_base' => 800,
                'unidades' => 1,
                'permite_turnos' => false,
                'orden' => 9,
            ],
            [
                'nombre' => 'Cabina Hinchable LED XXL',
                'slug' => 'cabina-hinchable-led-xxl',
                'descripcion' => 'Cabina hinchable gigante iluminada con LED: privacidad y mucho color.',
                'precio_base' => 530,
                'unidades' => 1,
                'permite_turnos' => false,
                'orden' => 10,
            ],
            [
                'nombre' => 'Cabaña Rústica',
                'slug' => 'cabana-rustica',
                'descripcion' => 'Cabaña de madera decorada, perfecta para bodas al aire libre y celebraciones rústicas.',
                'precio_base' => 800,
                'unidades' => 1,
                'permite_turnos' => false,
                'orden' => 11,
            ],
        ];

        foreach ($experiencias as $experiencia) {
            Experiencia::updateOrCreate(['slug' => $experiencia['slug']], [
                ...$experiencia,
                'descripcion' => $experiencia['descripcion'].self::ESTANDAR,
                'duracion_horas' => 3,
                'precio_hora_extra' => $experiencia['precio_hora_extra'] ?? null,
                'activo' => true,
            ]);
        }
    }

    /**
     * Qué complementos ofrece cada máquina.
     *
     * // DECISIÓN: todas ofrecen el catálogo completo (el comercio los vende sobre
     * cualquier máquina); las excepciones son los elementos que YA van incluidos en
     * una experiencia concreta, que se asocian con precio 0 y marcados como obligatorios.
     */
    private function asociaciones(): void
    {
        // slug => [grupo, cantidad_maxima] — el orden del array es el de presentación.
        $comunes = [
            'estructura' => [],
            'fondo-jardin' => [],
            'shimmer-wall' => [],
            'neon' => [],
            'sofa' => [],
            'alfombra' => [],
            'audiolibro-firmas' => ['grupo' => 'Audiolibro'],
            'audiolibro-video' => ['grupo' => 'Audiolibro'],
            'letras-iniciales' => ['grupo' => 'Letras'],
            'letras-love' => ['grupo' => 'Letras'],
            'pack-letras' => ['grupo' => 'Letras'],
            'glitter-corner-1' => ['grupo' => 'Glitter Corner'],
            'glitter-corner-2' => ['grupo' => 'Glitter Corner'],
            'hora-loca-a' => ['grupo' => 'Hora Loca'],
            'hora-loca-b' => ['grupo' => 'Hora Loca'],
            'hora-loca-c' => ['grupo' => 'Hora Loca'],
            'jeringas-shot' => ['cantidad_maxima' => 10],
            'inflable-tematico' => ['cantidad_maxima' => 10],
            'animador-extra' => ['cantidad_maxima' => 5],
            'pistolas-led-co2' => [],
            'mesa-dulce-golosinas' => ['grupo' => 'Mesa Dulce'],
            'mesa-dulce-donuts' => ['grupo' => 'Mesa Dulce'],
            'kiosko-moita-troula' => [],
            'puesto-palomitero' => [],
            'puesto-algodonero' => [],
            'puesto-hot-dog' => [],
            'puesto-gofres' => [],
            'puesto-crepes' => [],
            'puesto-helados' => [],
            'puesto-burguers' => [],
            'futbolin' => [],
            'arcade' => [],
            'portafoto-iman' => [],
            'invitaciones-madera' => [],
        ];

        // Complementos que una máquina concreta NO ofrece porque ya trae lo suyo:
        // el Fotomatón con Estructura y Neón elige entre los modelos concretos que
        // siembra VariantesRetratateSeeder, así que el genérico sobra.
        $excluidos = [
            'fotomaton-estructura-neon' => ['estructura', 'neon'],
        ];

        $idsComplementos = Complemento::pluck('id', 'slug');

        foreach (Experiencia::whereIn('slug', $this->slugsExperiencias())->get() as $experiencia) {
            $sync = [];
            $orden = 0;

            foreach ($comunes as $slug => $reglas) {
                $complementoId = $idsComplementos[$slug] ?? null;

                if ($complementoId === null || in_array($slug, $excluidos[$experiencia->slug] ?? [], true)) {
                    continue;
                }

                $sync[$complementoId] = [
                    'precio_override' => null,
                    'grupo' => $reglas['grupo'] ?? null,
                    'obligatorio' => false,
                    'cantidad_maxima' => $reglas['cantidad_maxima'] ?? 1,
                    'orden' => ++$orden,
                ];
            }

            $experiencia->complementos()->sync($sync);
        }
    }

    /**
     * Packs cerrados. `bases` = máquinas alternativas con su suplemento
     * (la base por defecto va sin suplemento).
     */
    private function packs(): void
    {
        // Suplementos de la tarifa por cambiar la máquina base del pack.
        // // DECISIÓN: la línea "Neón + Estructura + Sofá +250 €" se obtiene con la base
        // Estructura+Neón (+150) más el complemento Sofá (+100), que ya existe suelto.
        $suplementos = [
            'fotomaton-photocall-tela' => 30,
            'fotomaton-photocall-lentejuelas' => 30,
            'cabina-hinchable-led-xxl' => 80,
            'espejo-magico' => 80,
            'fotomaton-estructura-neon' => 150,
            'plataforma-360' => 50,
            'cabana-rustica' => 300,
        ];

        $packs = [
            [
                'nombre' => 'Pack Bronce',
                'slug' => 'pack-bronce',
                'base' => 'fotomaton-solo',
                'precio' => 700,
                'descripcion' => 'Fotomatón 3 h + Letras de madera con vuestras iniciales.',
                'complementos' => ['letras-iniciales' => 1],
                'bases' => $suplementos,
            ],
            [
                'nombre' => 'Pack Glitter',
                'slug' => 'pack-glitter',
                'base' => 'fotomaton-solo',
                'precio' => 790,
                'descripcion' => 'Fotomatón 3 h + Glitter Corner 3 h con una maquilladora.',
                'complementos' => ['glitter-corner-1' => 1],
                'bases' => $suplementos,
            ],
            [
                'nombre' => 'Pack Oro',
                'slug' => 'pack-oro',
                'base' => 'fotomaton-solo',
                'precio' => 970,
                'descripcion' => 'Fotomatón 3 h + Letras con vuestras iniciales + Glitter Corner 3 h con una maquilladora.',
                'complementos' => ['letras-iniciales' => 1, 'glitter-corner-1' => 1],
                'bases' => $suplementos,
            ],
            [
                'nombre' => 'Pack Full Fotomatón',
                'slug' => 'pack-full-fotomaton',
                'base' => 'fotomaton-solo',
                'precio' => 900,
                'descripcion' => 'Fotomatón 3 h + Plataforma 360º 3 h. Consulta el precio especial de Futbolín (130 €) y Máquina Arcade (100 €) con este pack.',
                'complementos' => [],
                'bases' => $suplementos,
            ],
            [
                'nombre' => 'Pack Glitter Loco',
                'slug' => 'pack-glitter-loco',
                'base' => 'fotomaton-solo',
                'precio' => 1200,
                'descripcion' => 'Glitter Bar 3 h + Hora Loca Pack A. Puedes subir a Pack B (+150 €) o Pack C (+250 €).',
                'complementos' => ['glitter-corner-1' => 1, 'hora-loca-a' => 1],
                'bases' => $suplementos,
            ],
            [
                'nombre' => 'Pack 360 Loco',
                'slug' => 'pack-360-loco',
                'base' => 'plataforma-360',
                'precio' => 1350,
                'descripcion' => 'Plataforma 360º + Hora Loca Pack A. Puedes subir a Pack B (+150 €) o Pack C (+250 €).',
                'complementos' => ['hora-loca-a' => 1],
                'bases' => [],
            ],
            [
                'nombre' => 'Pack Fotomatón Loco',
                'slug' => 'pack-fotomaton-loco',
                'base' => 'fotomaton-solo',
                'precio' => 1300,
                'descripcion' => 'Fotomatón 3 h + Hora Loca Pack A. Puedes subir a Pack B (+150 €) o Pack C (+250 €).',
                'complementos' => ['hora-loca-a' => 1],
                'bases' => $suplementos,
            ],
            [
                'nombre' => 'Pack Fiesta Loca',
                'slug' => 'pack-fiesta-loca',
                'base' => 'fotomaton-solo',
                'precio' => 1650,
                'descripcion' => 'Fotomatón 3 h o Plataforma 360º + Glitter Bar + Hora Loca Pack A. Puedes subir a Pack B (+150 €) o Pack C (+250 €).',
                'complementos' => ['glitter-corner-1' => 1, 'hora-loca-a' => 1],
                // La tarifa ofrece fotomatón O 360 al mismo precio.
                'bases' => ['plataforma-360' => 0],
            ],
            [
                'nombre' => 'Pack Locura Total',
                'slug' => 'pack-locura-total',
                'base' => 'fotomaton-solo',
                'precio' => 2100,
                'descripcion' => 'Fotomatón 3 h + Plataforma 360º + Glitter Bar + Hora Loca Pack A. Puedes subir a Pack B (+150 €) o Pack C (+250 €).',
                'complementos' => ['glitter-corner-1' => 1, 'hora-loca-a' => 1],
                'bases' => [],
            ],
        ];

        $idsComplementos = Complemento::pluck('id', 'slug');
        $idsExperiencias = Experiencia::pluck('id', 'slug');

        foreach ($packs as $datos) {
            $pack = Pack::updateOrCreate(['slug' => $datos['slug']], [
                'experiencia_id' => $idsExperiencias[$datos['base']],
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'],
                'precio' => $datos['precio'],
                'activo' => true,
            ]);

            $syncComplementos = [];
            foreach ($datos['complementos'] as $slug => $cantidad) {
                if (isset($idsComplementos[$slug])) {
                    $syncComplementos[$idsComplementos[$slug]] = ['cantidad' => $cantidad];
                }
            }
            $pack->complementos()->sync($syncComplementos);

            $syncBases = [];
            $orden = 0;
            foreach ($datos['bases'] as $slug => $suplemento) {
                // La base por defecto ya se ofrece sin suplemento; no se duplica.
                if ($slug === $datos['base'] || ! isset($idsExperiencias[$slug])) {
                    continue;
                }

                $syncBases[$idsExperiencias[$slug]] = ['suplemento' => $suplemento, 'orden' => ++$orden];
            }
            $pack->basesDisponibles()->sync($syncBases);
        }
    }

    /**
     * @return array<int, string>
     */
    private function slugsExperiencias(): array
    {
        return [
            'fotomaton-solo',
            'fotomaton-photocall-tela',
            'fotomaton-photocall-lentejuelas',
            'fotomaton-estructura-neon',
            'fotomaton-beauty-glam',
            'fotomaton-beauty-glam-premium',
            'espejo-magico',
            'plataforma-360',
            'aereo-360',
            'cabina-hinchable-led-xxl',
            'cabana-rustica',
        ];
    }
}
