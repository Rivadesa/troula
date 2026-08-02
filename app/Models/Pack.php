<?php

namespace App\Models;

use Database\Factories\PackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pack extends Model
{
    /** @use HasFactory<PackFactory> */
    use HasFactory;

    protected $table = 'packs';

    protected $fillable = [
        'experiencia_id',
        'nombre',
        'slug',
        'descripcion',
        'imagen',
        'precio',
        'activo',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'activo' => 'boolean',
    ];

    /**
     * @return BelongsTo<Experiencia, $this>
     */
    public function experiencia(): BelongsTo
    {
        return $this->belongsTo(Experiencia::class, 'experiencia_id');
    }

    /**
     * Máquinas base que admite el pack, con el suplemento de cambiar a cada una.
     * La base por defecto (`experiencia_id`) se ofrece siempre con suplemento 0
     * aunque no esté en el pivote; ver `Pack::suplementoPara()`.
     *
     * @return BelongsToMany<Experiencia, $this>
     */
    public function basesDisponibles(): BelongsToMany
    {
        return $this->belongsToMany(Experiencia::class, 'pack_experiencia')
            ->withPivot(['suplemento', 'orden'])
            ->withTimestamps()
            ->orderByPivot('orden');
    }

    /**
     * Suplemento por usar `$experienciaId` como máquina base de este pack.
     * La base por defecto nunca lleva suplemento; una experiencia que no sea
     * base admitida tampoco suma (el motor de precios la trata como la base).
     */
    public function suplementoPara(?int $experienciaId): float
    {
        if ($experienciaId === null || $experienciaId === $this->experiencia_id) {
            return 0.0;
        }

        $base = $this->basesDisponibles->firstWhere('id', $experienciaId);

        return $base !== null ? round((float) $base->pivot->suplemento, 2) : 0.0;
    }

    /**
     * ¿Se puede usar esta experiencia como base del pack?
     */
    public function admiteBase(?int $experienciaId): bool
    {
        if ($experienciaId === null) {
            return false;
        }

        return $experienciaId === $this->experiencia_id
            || $this->basesDisponibles->contains('id', $experienciaId);
    }

    /**
     * Complementos incluidos en el pack (su precio ya va dentro del precio cerrado).
     *
     * @return BelongsToMany<Complemento, $this>
     */
    public function complementos(): BelongsToMany
    {
        return $this->belongsToMany(Complemento::class, 'pack_complemento')
            ->withPivot('cantidad')
            ->withTimestamps();
    }
}
