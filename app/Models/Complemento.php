<?php

namespace App\Models;

use Database\Factories\ComplementoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Complemento extends Model
{
    /** @use HasFactory<ComplementoFactory> */
    use HasFactory;

    protected $table = 'complementos';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'slug',
        'descripcion',
        'precio',
        'imagen',
        'activo',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'activo' => 'boolean',
    ];

    /**
     * @return BelongsTo<CategoriaComplemento, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaComplemento::class, 'categoria_id');
    }

    /**
     * @return BelongsToMany<Experiencia, $this>
     */
    public function experiencias(): BelongsToMany
    {
        return $this->belongsToMany(Experiencia::class, 'experiencia_complemento')
            ->withPivot(['precio_override', 'obligatorio', 'cantidad_maxima', 'orden'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Pack, $this>
     */
    public function packs(): BelongsToMany
    {
        return $this->belongsToMany(Pack::class, 'pack_complemento')
            ->withPivot('cantidad')
            ->withTimestamps();
    }
}
