<?php

namespace App\Models;

use Database\Factories\ExperienciaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Experiencia extends Model
{
    /** @use HasFactory<ExperienciaFactory> */
    use HasFactory;

    protected $table = 'experiencias';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'precio_base',
        'imagen',
        'unidades',
        'permite_turnos',
        'activo',
        'orden',
    ];

    protected $casts = [
        'precio_base' => 'decimal:2',
        'unidades' => 'integer',
        'permite_turnos' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Complementos que ofrece esta experiencia, con sus reglas (pivote).
     *
     * @return BelongsToMany<Complemento, $this>
     */
    public function complementos(): BelongsToMany
    {
        return $this->belongsToMany(Complemento::class, 'experiencia_complemento')
            ->withPivot(['precio_override', 'obligatorio', 'cantidad_maxima', 'orden'])
            ->withTimestamps()
            ->orderByPivot('orden');
    }

    /**
     * @return HasMany<Pack, $this>
     */
    public function packs(): HasMany
    {
        return $this->hasMany(Pack::class, 'experiencia_id');
    }

    /**
     * @return HasMany<Reserva, $this>
     */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'experiencia_id');
    }
}
