<?php

namespace App\Models;

use Database\Factories\CategoriaComplementoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaComplemento extends Model
{
    /** @use HasFactory<CategoriaComplementoFactory> */
    use HasFactory;

    protected $table = 'categorias_complemento';

    protected $fillable = [
        'nombre',
        'slug',
        'imagen',
        'orden',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    /**
     * @return HasMany<Complemento, $this>
     */
    public function complementos(): HasMany
    {
        return $this->hasMany(Complemento::class, 'categoria_id');
    }
}
