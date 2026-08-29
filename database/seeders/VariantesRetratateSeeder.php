<?php

namespace Database\Seeders;

use App\Models\CategoriaComplemento;
use App\Models\Complemento;
use App\Models\Experiencia;
use Illuminate\Database\Seeder;

/**
 * Modelos concretos que el cliente elige DENTRO de una máquina: la tela del
 * photocall, la lentejuela, la estructura, el neón y el sofá.
 *
 * En el PDF la página del Fotomatón con Estructura y Neón lo dice tal cual:
 * "1- ELIGE LA ESTRUCTURA / 2- ELIGE EL NEÓN / 3- ELIGE EL SOFÁ".
 *
 * Se modelan como complementos normales agrupados con el pivote `grupo`, que ya
 * hace que en el configurador se comporten como "elige uno". Lo que va incluido
 * en el precio de la máquina se asocia con `precio_override = 0`.
 *
 * // DECISIÓN: los neones y los sofás sustituyen a los complementos genéricos
 * "Neón" (80 €) y "Sofá" (100 €), que quedan desactivados: así el cliente elige
 * siempre un modelo concreto en vez de un genérico sin definir.
 *
 * Lo llama `CatalogoRetratateSeeder` al final, porque aquel hace `sync()` de las
 * asociaciones y borraría estas si se ejecutase después.
 */
class VariantesRetratateSeeder extends Seeder
{
    /** Telas del photocall (incluidas en el precio de su fotomatón). */
    private const TELAS = [
        'tela-tropical-palmeras' => 'Tropical · palmeras',
        'tela-gran-viaje' => 'Nuestro gran viaje',
        'tela-madera-banderines' => 'Madera con banderines',
        'tela-love-is-in-the-air' => 'Love is in the air',
        'tela-tropical-rosa' => 'Tropical rosa',
        'tela-globos' => 'Globos en el cielo',
        'tela-flamenco-love' => 'Flamenco Love',
        'tela-topos-dorados' => 'Topos dorados',
        'tela-corazones-colores' => 'Corazones de colores',
        'tela-nuestra-boda' => 'Nuestra Boda (personalizada)',
        'tela-madera-luces' => 'Madera con luces',
        'tela-corazones-rosas' => 'Corazones rosas',
        'tela-hojas-acuarela' => 'Hojas en acuarela',
        'tela-rosas-fucsia' => 'Rosas fucsia',
        'tela-flores-silvestres' => 'Flores silvestres',
        'tela-rosa-corazones' => 'Rosa con corazones',
        'tela-hojas-doradas' => 'Hojas doradas',
        'tela-londres' => 'Londres',
        'tela-helados' => 'Helados',
    ];

    /** Telas de lentejuelas (incluidas en el precio de su fotomatón). */
    private const LENTEJUELAS = [
        'lentejuelas-plata' => 'Lentejuelas plata',
        'lentejuelas-holografica' => 'Lentejuelas holográfica',
    ];

    /** Estructuras del Fotomatón con Estructura y Neón (nombres del propio PDF). */
    private const ESTRUCTURAS = [
        'estructura-fondo-jardin-vertical' => 'Fondo jardín vertical',
        'estructura-jardin-flores' => 'Jardín de flores',
        'estructura-hexagono-madera' => 'Hexágono de madera',
        'estructura-triangulo-madera' => 'Triángulo de madera',
        'estructura-cuadrado-metal' => 'Cuadrado de metal',
        'estructura-tela' => 'Tela',
        'estructura-lentejuelas' => 'Lentejuelas',
        'estructura-shimmer-wall' => 'Shimmer Wall de lentejuelas',
    ];

    /** Neones: 80 € sueltos, incluidos en el Fotomatón con Estructura y Neón. */
    private const NEONES = [
        'neon-fin-del-mundo-arco' => 'Que el fin del mundo nos pille bailando (arco)',
        'neon-fin-del-mundo' => 'Que el fin del mundo nos pille bailando',
        'neon-sempre-ti' => 'Sempre ti',
        'neon-me-quedo-contigo' => 'Me quedo contigo',
        'neon-si-quiero' => 'Sí quiero',
        'neon-a-los-locos' => 'A los locos nos verán bailando',
        'neon-querote' => 'Quérote',
        'neon-contigo-al-fin-del-mundo' => 'Contigo al fin del mundo',
        'neon-mejor-equipo' => 'Juntos somos el mejor equipo',
        'neon-que-no-sea-contigo' => 'Que ya no quiero nada que no sea contigo',
        'neon-siempre-seras-tu' => 'Siempre serás tú',
        'neon-la-vida-es-una-verbena' => 'La vida es una verbena',
        'neon-ata-o-infinito' => 'Ata o infinito e máis alá',
        'neon-cala-e-bicame' => 'Cala e bícame',
        'neon-aqui-empeza' => 'Aquí empeza a nosa viaxe',
        'neon-contigo-todo' => 'Contigo todo',
        'neon-el-amor-todo-locura' => 'El amor todo locura',
        'neon-love-is-love' => 'Love is love',
        'neon-juntos-es-mejor' => 'Juntos es mejor',
        'neon-aqui-se-lia-parda' => 'Aquí se lía parda',
    ];

    /** Sofás: 100 € (es lo que sube el fotomatón de estructura y neón, 600 → 700). */
    private const SOFAS = [
        'sofa-rosa-palo' => 'Sofá rosa palo',
        'sofa-chester-marron' => 'Sofá Chester marrón',
        'sofa-azul' => 'Sofá azul',
        'sofa-malva' => 'Sofá malva',
        'sofa-amarillo' => 'Sofá amarillo',
        'sofa-verde' => 'Sofá verde',
        'sofa-rosa-claro' => 'Sofá rosa claro',
        'sofa-rojo' => 'Sofá rojo',
    ];

    public function run(): void
    {
        $this->categorias();

        $this->crear(self::TELAS, 'telas', 0);
        $this->crear(self::LENTEJUELAS, 'lentejuelas', 0);
        $this->crear(self::ESTRUCTURAS, 'estructuras', 0);
        $this->crear(self::NEONES, 'neones', 80);
        $this->crear(self::SOFAS, 'sofas', 100);

        // Los genéricos quedan sustituidos por los modelos concretos.
        Complemento::whereIn('slug', ['neon', 'sofa'])->update(['activo' => false]);

        $this->asociar();

        $this->command?->info('Variantes: '.
            count(self::TELAS).' telas, '.count(self::LENTEJUELAS).' lentejuelas, '.
            count(self::ESTRUCTURAS).' estructuras, '.count(self::NEONES).' neones, '.
            count(self::SOFAS).' sofás.');
    }

    private function categorias(): void
    {
        $categorias = [
            ['nombre' => 'Telas del photocall', 'slug' => 'telas', 'orden' => 10],
            ['nombre' => 'Telas de lentejuelas', 'slug' => 'lentejuelas', 'orden' => 11],
            ['nombre' => 'Estructuras', 'slug' => 'estructuras', 'orden' => 12],
            ['nombre' => 'Neones', 'slug' => 'neones', 'orden' => 13],
            ['nombre' => 'Sofás', 'slug' => 'sofas', 'orden' => 14],
        ];

        foreach ($categorias as $categoria) {
            CategoriaComplemento::updateOrCreate(['slug' => $categoria['slug']], $categoria);
        }
    }

    /**
     * @param  array<string, string>  $items  slug => nombre
     */
    private function crear(array $items, string $slugCategoria, float $precio): void
    {
        $categoria = CategoriaComplemento::where('slug', $slugCategoria)->firstOrFail();

        foreach ($items as $slug => $nombre) {
            Complemento::updateOrCreate(['slug' => $slug], [
                'nombre' => $nombre,
                'precio' => $precio,
                'a_consultar' => false,
                'categoria_id' => $categoria->id,
                'activo' => true,
            ]);
        }
    }

    /**
     * Asocia cada familia a las máquinas que la ofrecen.
     *
     * `incluido = true` => precio_override 0: la elección no cuesta más porque ya
     * va dentro del precio de la máquina.
     */
    private function asociar(): void
    {
        $ids = Complemento::pluck('id', 'slug');

        // Elecciones propias de una máquina concreta (van incluidas en su precio).
        $propias = [
            'fotomaton-photocall-tela' => [['grupo' => 'Tela', 'items' => self::TELAS, 'incluido' => true]],
            'fotomaton-photocall-lentejuelas' => [['grupo' => 'Lentejuelas', 'items' => self::LENTEJUELAS, 'incluido' => true]],
            'fotomaton-estructura-neon' => [
                ['grupo' => 'Estructura', 'items' => self::ESTRUCTURAS, 'incluido' => true],
                ['grupo' => 'Neón', 'items' => self::NEONES, 'incluido' => true],
                // El sofá SÍ se cobra: es lo que separa los 600 € de los 700 €.
                ['grupo' => 'Sofá', 'items' => self::SOFAS, 'incluido' => false],
            ],
        ];

        foreach (Experiencia::all() as $experiencia) {
            $bloques = $propias[$experiencia->slug] ?? [];

            // Neones y sofás se pueden añadir sobre cualquier máquina, de pago,
            // salvo que ya se hayan asociado arriba como elección incluida.
            $gruposPropios = array_column($bloques, 'grupo');
            foreach ([['Neón', self::NEONES], ['Sofá', self::SOFAS]] as [$grupo, $items]) {
                if (! in_array($grupo, $gruposPropios, true)) {
                    $bloques[] = ['grupo' => $grupo, 'items' => $items, 'incluido' => false];
                }
            }

            // Orden negativo: las elecciones de la máquina se muestran ANTES que
            // los complementos generales, que empiezan en 1.
            $orden = -100;
            $sync = [];

            foreach ($bloques as $bloque) {
                foreach (array_keys($bloque['items']) as $slug) {
                    if (! isset($ids[$slug])) {
                        continue;
                    }

                    $sync[$ids[$slug]] = [
                        'precio_override' => $bloque['incluido'] ? 0 : null,
                        'grupo' => $bloque['grupo'],
                        'obligatorio' => false,
                        'cantidad_maxima' => 1,
                        'orden' => $orden++,
                    ];
                }
            }

            $experiencia->complementos()->syncWithoutDetaching($sync);
        }
    }
}
