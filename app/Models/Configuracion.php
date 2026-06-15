<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Datos de la empresa. Fila única (singleton) editable desde el panel.
 * Se cachea para no consultar la BD en cada render del frontend.
 */
class Configuracion extends Model
{
    protected $table = 'configuracion';

    protected $guarded = [];

    private const CACHE_KEY = 'configuracion.empresa';

    /**
     * Devuelve la configuración actual (la crea con valores por defecto si no existe).
     */
    public static function actual(): self
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): self => static::query()->firstOrCreate(
            ['id' => 1],
            [
                'nombre' => 'Troula Eventos',
                'eslogan' => 'Fotomatones y experiencias para tu evento',
                'email' => 'info@troula.test',
                'ciudad' => 'A Coruña',
            ],
        ));
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }
}
