<?php

namespace App\Models;

use App\Enums\TipoAjuste;
use Database\Factories\TemporadaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Temporada extends Model
{
    /** @use HasFactory<TemporadaFactory> */
    use HasFactory;

    protected $table = 'temporadas';

    protected $fillable = [
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'tipo_ajuste',
        'valor',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'tipo_ajuste' => TipoAjuste::class,
        'valor' => 'decimal:2',
        'activo' => 'boolean',
    ];
}
