<?php

namespace App\Models;

use Database\Factories\ZonaPorteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ZonaPorte extends Model
{
    /** @use HasFactory<ZonaPorteFactory> */
    use HasFactory;

    protected $table = 'zonas_porte';

    protected $fillable = [
        'nombre',
        'precio_porte',
        'precio_montaje',
    ];

    protected $casts = [
        'precio_porte' => 'decimal:2',
        'precio_montaje' => 'decimal:2',
    ];

    /**
     * @return HasMany<ConcelloZona, $this>
     */
    public function concellos(): HasMany
    {
        return $this->hasMany(ConcelloZona::class, 'zona_id');
    }

    /**
     * @return HasMany<Reserva, $this>
     */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'zona_id');
    }
}
