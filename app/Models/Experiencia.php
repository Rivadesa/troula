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
        'duracion_horas',
        'precio_hora_extra',
        'imagen',
        'unidades',
        'permite_turnos',
        'activo',
        'orden',
    ];

    protected $casts = [
        'precio_base' => 'decimal:2',
        'duracion_horas' => 'integer',
        'precio_hora_extra' => 'decimal:2',
        'unidades' => 'integer',
        'permite_turnos' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Solo se ofrecen horas extra si el admin ha puesto precio (> 0).
     */
    public function admiteHorasExtra(): bool
    {
        return $this->precio_hora_extra !== null && (float) $this->precio_hora_extra > 0;
    }

    /**
     * Complementos que ofrece esta experiencia, con sus reglas (pivote).
     *
     * @return BelongsToMany<Complemento, $this>
     */
    public function complementos(): BelongsToMany
    {
        return $this->belongsToMany(Complemento::class, 'experiencia_complemento')
            ->withPivot(['precio_override', 'grupo', 'obligatorio', 'cantidad_maxima', 'orden'])
            ->withTimestamps()
            ->orderByPivot('orden');
    }

    /**
     * Packs cuya base POR DEFECTO es esta experiencia.
     *
     * @return HasMany<Pack, $this>
     */
    public function packs(): HasMany
    {
        return $this->hasMany(Pack::class, 'experiencia_id');
    }

    /**
     * Packs en los que esta experiencia se ofrece como base alternativa (con suplemento).
     *
     * @return BelongsToMany<Pack, $this>
     */
    public function packsComoBase(): BelongsToMany
    {
        return $this->belongsToMany(Pack::class, 'pack_experiencia')
            ->withPivot(['suplemento', 'orden'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Reserva, $this>
     */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'experiencia_id');
    }
}
