<?php

namespace App\Console\Commands;

use App\Models\Configuracion;
use Illuminate\Console\Command;
use ZipArchive;

/**
 * Carga la plantilla del contrato a partir del .docx del cliente.
 *
 *   php artisan contrato:cargar-plantilla "C:\ruta\CONTRATO ... .docx"
 *
 * // DECISIÓN: el texto del contrato NO se versiona en el repositorio (es
 * público, y el documento original venía relleno con los datos personales de
 * una pareja real). Vive solo en la BD, editable desde el panel, y este comando
 * permite recargarlo cuando el cliente entregue una versión nueva.
 *
 * El comando sustituye por huecos {{clave}} los datos del ejemplo que traía el
 * documento; lo que no reconozca queda tal cual para que el admin lo repase.
 */
class CargarPlantillaContrato extends Command
{
    protected $signature = 'contrato:cargar-plantilla {fichero : Ruta al .docx del contrato} {--forzar : Sustituye la plantilla existente}';

    protected $description = 'Carga en la configuración la plantilla del contrato desde un .docx';

    /**
     * Datos del ejemplo que trae el documento original -> hueco de plantilla.
     * Se aplican en orden; los más largos primero para no partir los cortos.
     */
    private const SUSTITUCIONES = [
        // Prestador
        'ADRIÁN CANCELA BLANCO' => '{{titular_nombre}}',
        'Adrián Cancela Blanco' => '{{titular_nombre}}',
        '78804214G' => '{{titular_dni}}',
        'Avda. Tome Taboada, 89 –15840 Santa Comba [A Coruña]' => '{{empresa_direccion}}',
        'ES07 0182 1294 1002 0114 3519' => '{{iban}}',
        'info@retratate.es' => '{{empresa_email}}',

        // Cliente del ejemplo
        'Óscar Alejandro Alemán Gramajo' => '{{cliente_nombre}}',
        '49199828E' => '{{cliente_dni}}',
        '687081016' => '{{cliente_telefono}}',
        'Alba López Varela' => '',
        '32717262S' => '',
        '608363932' => '',
        'Rúa Amil 18G , Cambre 15660' => '{{cliente_direccion}}',
        'oscaraleman92@gmail.com' => '{{cliente_email}}',

        // Evento del ejemplo
        '15 AGOSTO 2026' => '{{fecha_evento}}',
        // Sin paréntesis con el concello: {{lugar_evento}} ya lo incluye cuando
        // hay un lugar concreto, y cae al concello solo cuando no lo hay.
        'A TORRE DE LAXE, LAXE.' => '{{lugar_evento}}',
        'En A Coruña, 26 de JUNIO de 2026.' => 'En {{concello}}, {{fecha_hoy}}.',

        // El documento original era solo para bodas. Se generaliza a INTERESADOS.
        //
        // // DECISIÓN: el término definido va en PLURAL aunque el formulario
        // recoja un único contratante. El texto está escrito en plural ("los
        // NOVIOS deberán…") y pasarlo a singular obligaría a reescribir la
        // concordancia de decenas de frases de un documento legal. Con "los
        // INTERESADOS" la gramática se mantiene intacta.
        'La pareja de novios (a partir de ahora denominada “NOVIOS”) a continuación nombrados, contrata a'
            => 'El o los interesados (a partir de ahora denominados “LOS INTERESADOS”) a continuación nombrados, contratan a',
        'reuniones previas a la boda con los NOVIOS' => 'reuniones previas al evento con los INTERESADOS',
        'el día de la boda contratado' => 'el día del evento contratado',
        'alguno de los cónyuges' => 'alguno de los INTERESADOS',
        'uno de los cónyuges' => 'uno de los INTERESADOS',
        // En minúscula aparece suelto en varias cláusulas. Va con el artículo
        // delante para no tocar "noviembre", que también contiene "novi".
        'los novios' => 'los INTERESADOS',
        'del domicilio de los novios o familia' => 'del domicilio de los INTERESADOS o familia',
        'la celebración de alguna parte de la boda' => 'la celebración de alguna parte del evento',

        // El importe de la señal deja de ir escrito a fuego: lo pone la
        // plataforma, así el contrato no puede contradecir lo que se cobra.
        'la cantidad de 100€ (u otra cantidad solicitada por los novios)'
            => 'la cantidad de {{senal}} (u otra cantidad acordada con los INTERESADOS)',

        // Rótulo del bloque de datos. Va antes que la sustitución general:
        // strtr elige siempre la coincidencia más larga en cada posición.
        'NOVIOS:' => 'INTERESADO:',
        'NOVIOS' => 'INTERESADOS',
    ];

    /**
     * El bloque del servicio contratado del ejemplo se sustituye entero: cada
     * reserva genera el suyo.
     */
    private const BLOQUE_SERVICIO = [
        'desde' => 'TIPO DE SERVICIO:',
        'hasta' => 'HORAS EXTRAS:',
        'nuevo' => "TIPO DE SERVICIO:\n\n{{servicio}}\n\n"
            ."IMPORTE TOTAL: {{total}}\n"
            ."SEÑAL A ABONAR PARA RESERVAR LA FECHA: {{senal}}\n"
            ."RESTO A ABONAR ANTES DEL EVENTO: {{resto}}\n\n"
            .'HORAS EXTRAS:',
    ];

    public function handle(): int
    {
        $fichero = (string) $this->argument('fichero');

        if (! is_file($fichero)) {
            $this->error("No encuentro el fichero: {$fichero}");

            return self::FAILURE;
        }

        $config = Configuracion::actual();

        if (filled($config->contrato_plantilla) && ! $this->option('forzar')) {
            $this->warn('Ya hay una plantilla guardada. Usa --forzar para sustituirla.');

            return self::FAILURE;
        }

        $texto = $this->textoDelDocx($fichero);

        if ($texto === null) {
            $this->error('No se pudo leer el documento (¿es un .docx válido?).');

            return self::FAILURE;
        }

        $plantilla = $this->sustituirBloqueServicio($texto);
        $plantilla = strtr($plantilla, self::SUSTITUCIONES);
        $plantilla = $this->limpiarLineasVacias($plantilla);

        $config->update(['contrato_plantilla' => $plantilla]);

        $this->info('Plantilla cargada: '.mb_strlen($plantilla).' caracteres.');
        $this->line('Huecos detectados: '.implode(', ', $this->huecos($plantilla)));
        $this->newLine();
        $this->comment('Repásala en Panel → Configuración → Contrato antes de usarla.');

        return self::SUCCESS;
    }

    /**
     * Quita las líneas que se quedan sin contenido tras las sustituciones.
     *
     * El documento original tenía DOS firmantes (los novios). Al vaciar los
     * datos del segundo queda una línea "D/Dña. , DNI , Teléfono: ." que hay que
     * borrar aparte: `strtr` es de una sola pasada y no puede eliminar algo que
     * la propia sustitución acaba de crear.
     */
    private function limpiarLineasVacias(string $plantilla): string
    {
        $limpia = preg_replace('/^D\/Dña\.\s*,\s*DNI\s*,\s*Teléfono:\s*\.\s*$\n?/mu', '', $plantilla);

        return $limpia ?? $plantilla;
    }

    /**
     * Cambia el detalle del servicio del ejemplo por el hueco {{servicio}}.
     */
    private function sustituirBloqueServicio(string $texto): string
    {
        $inicio = mb_strpos($texto, self::BLOQUE_SERVICIO['desde']);
        $fin = mb_strpos($texto, self::BLOQUE_SERVICIO['hasta']);

        if ($inicio === false || $fin === false || $fin <= $inicio) {
            $this->warn('No he localizado el bloque del servicio contratado: revísalo a mano.');

            return $texto;
        }

        return mb_substr($texto, 0, $inicio)
            .self::BLOQUE_SERVICIO['nuevo']
            .mb_substr($texto, $fin + mb_strlen(self::BLOQUE_SERVICIO['hasta']));
    }

    /**
     * Extrae el texto plano de un .docx conservando los saltos de párrafo.
     */
    private function textoDelDocx(string $fichero): ?string
    {
        $zip = new ZipArchive;

        if ($zip->open($fichero) !== true) {
            return null;
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            return null;
        }

        // Un párrafo por línea; las tabulaciones se conservan.
        $xml = str_replace(['</w:p>', '<w:tab/>', '<w:br/>'], ["\n", "\t", "\n"], $xml);
        $texto = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');

        // Limpia líneas en blanco repetidas y espacios sobrantes.
        $lineas = array_map(trim(...), explode("\n", $texto));
        $texto = implode("\n", $lineas);

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $texto));
    }

    /**
     * @return array<int, string>
     */
    private function huecos(string $plantilla): array
    {
        preg_match_all('/\{\{\s*([a-z_]+)\s*\}\}/i', $plantilla, $encontrados);

        return array_values(array_unique($encontrados[1]));
    }
}
