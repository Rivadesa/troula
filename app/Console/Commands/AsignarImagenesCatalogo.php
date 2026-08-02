<?php

namespace App\Console\Commands;

use App\Models\Complemento;
use App\Models\Experiencia;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Enlaza cada experiencia y complemento con su foto del catálogo.
 *
 * Las imágenes las deja `scripts/imagenes-catalogo.py` en
 * storage/app/public/{experiencias,complementos}/<slug>.jpg. Este comando solo
 * escribe la ruta en la columna `imagen` de los registros cuyo fichero existe.
 *
 * Idempotente. Por defecto respeta lo que ya tenga imagen (para no pisar lo que
 * el cliente haya subido desde el panel); usa --forzar para reasignar todo.
 */
class AsignarImagenesCatalogo extends Command
{
    protected $signature = 'catalogo:asignar-imagenes {--forzar : Reasigna también los que ya tienen imagen}';

    protected $description = 'Asigna a experiencias y complementos las fotos del catálogo presentes en storage';

    public function handle(): int
    {
        $total = 0;
        $total += $this->asignar(Experiencia::class, 'experiencias');
        $total += $this->asignar(Complemento::class, 'complementos');

        $this->info("Imágenes asignadas: {$total}.");

        return self::SUCCESS;
    }

    /**
     * @param  class-string<Model>  $modelo
     */
    private function asignar(string $modelo, string $carpeta): int
    {
        $asignadas = 0;
        $sinFoto = [];

        foreach ($modelo::query()->orderBy('id')->get() as $registro) {
            $ruta = "{$carpeta}/{$registro->slug}.jpg";

            if (! Storage::disk('public')->exists($ruta)) {
                $sinFoto[] = $registro->slug;

                continue;
            }

            if (filled($registro->imagen) && ! $this->option('forzar')) {
                continue;
            }

            $registro->update(['imagen' => $ruta]);
            $asignadas++;
        }

        $this->line("  {$carpeta}: {$asignadas} asignadas".
            ($sinFoto === [] ? '' : ', sin foto en el PDF: '.implode(', ', $sinFoto)));

        return $asignadas;
    }
}
