<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copia los datos del fichero SQLite a la conexión por defecto de la app
 * (MySQL/MariaDB o PostgreSQL). Pensado para la migración única sin perder datos.
 *
 * Uso (con la app ya apuntando a MySQL y el esquema migrado):
 *   php artisan db:copiar-a-mysql
 */
class CopiarDatosAMysql extends Command
{
    protected $signature = 'db:copiar-a-mysql {--file= : Ruta del fichero SQLite de origen}';

    protected $description = 'Copia los datos del SQLite a la conexión por defecto sin perder nada.';

    /**
     * Tablas en orden padre → hijo (se omiten sessions/cache/jobs/migrations, efímeras).
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

    /**
     * Columnas booleanas por tabla (para convertir 0/1 → bool al insertar).
     *
     * @var array<string, array<int, string>>
     */
    private array $columnasBool = [
        'experiencias' => ['permite_turnos', 'activo'],
        'complementos' => ['activo'],
        'experiencia_complemento' => ['obligatorio'],
        'packs' => ['activo'],
        'temporadas' => ['activo'],
        'clientes' => ['acepto_lopd'],
    ];

    public function handle(): int
    {
        // Conexión de origen con ruta fija al SQLite (independiente de DB_DATABASE).
        config(['database.connections.sqlite_origen' => [
            'driver' => 'sqlite',
            'database' => $this->option('file') ?: database_path('database.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        $origen = 'sqlite_origen';

        if (! file_exists((string) config("database.connections.{$origen}.database"))) {
            $this->error('No se encuentra el fichero SQLite de origen.');

            return self::FAILURE;
        }

        $destino = config('database.default');
        $this->info("Copiando datos de SQLite → '{$destino}'...");
        $esPostgres = DB::getDriverName() === 'pgsql';

        // 1) Vaciar el destino en orden inverso (respeta las claves foráneas).
        foreach (array_reverse($this->tablas) as $tabla) {
            if (Schema::hasTable($tabla)) {
                DB::table($tabla)->delete();
            }
        }

        // 2) Copiar en orden padre → hijo.
        foreach ($this->tablas as $tabla) {
            if (! Schema::connection($origen)->hasTable($tabla)) {
                $this->warn("· {$tabla}: no existe en el origen, omitida.");

                continue;
            }

            $bool = $this->columnasBool[$tabla] ?? [];
            $total = 0;

            DB::connection($origen)->table($tabla)->orderBy('id')->chunk(500, function ($filas) use ($tabla, $bool, &$total): void {
                $datos = array_map(function ($fila) use ($bool): array {
                    $fila = (array) $fila;
                    foreach ($bool as $col) {
                        if (array_key_exists($col, $fila) && $fila[$col] !== null) {
                            $fila[$col] = (bool) $fila[$col];
                        }
                    }

                    return $fila;
                }, $filas->all());

                DB::table($tabla)->insert($datos);
                $total += count($datos);
            });

            // 3) PostgreSQL: reiniciar la secuencia del id tras insertar ids explícitos.
            if ($esPostgres && ($max = DB::table($tabla)->max('id'))) {
                DB::statement("SELECT setval(pg_get_serial_sequence('{$tabla}', 'id'), {$max})");
            }

            $this->line("· {$tabla}: {$total} filas");
        }

        $this->info('Copia completada.');

        return self::SUCCESS;
    }
}
