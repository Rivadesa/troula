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
