<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copia los datos de una conexión de origen (por defecto `sqlite`) a la conexión
 * por defecto de la app (MySQL en producción). Pensado para la migración única
 * SQLite → MySQL sin perder datos.
 *
 * Uso (con la app ya apuntando a MySQL y el esquema migrado):
 *   php artisan db:copiar-a-mysql
 */
class CopiarDatosAMysql extends Command
{
    protected $signature = 'db:copiar-a-mysql {--from=sqlite : Conexión de origen}';

    protected $description = 'Copia los datos de SQLite (u otra conexión) a la conexión por defecto.';

    /**
     * Tablas de aplicación a copiar (se omiten sessions/cache/jobs/migrations, efímeras).
     *
     * @var array<int, string>
     */
    private array $tablas = [
        'users',
        'configuracion',
        'categorias_complemento',
        'complementos',
        'experiencias',
        'experiencia_complemento',
        'packs',
        'pack_complemento',
        'temporadas',
        'zonas_porte',
        'concello_zona',
        'clientes',
        'reservas',
        'reserva_complemento',
        'pagos',
    ];

    public function handle(): int
    {
        $origen = (string) $this->option('from');

        if ($origen === config('database.default')) {
            $this->error('La conexión de origen y la de destino son la misma ('.$origen.').');

            return self::FAILURE;
        }

        $this->info("Copiando datos desde '{$origen}' hacia '".config('database.default')."'...");

        Schema::disableForeignKeyConstraints();

        foreach ($this->tablas as $tabla) {
            if (! Schema::connection($origen)->hasTable($tabla)) {
                $this->warn("· {$tabla}: no existe en el origen, omitida.");

                continue;
            }

            DB::table($tabla)->truncate();

            $total = 0;
            DB::connection($origen)->table($tabla)->orderBy('id')->chunk(500, function ($filas) use ($tabla, &$total): void {
                $datos = array_map(fn ($fila): array => (array) $fila, $filas->all());
                DB::table($tabla)->insert($datos);
                $total += count($datos);
            });

            $this->line("· {$tabla}: {$total} filas");
        }

        Schema::enableForeignKeyConstraints();

        $this->info('Copia completada.');

        return self::SUCCESS;
    }
}
