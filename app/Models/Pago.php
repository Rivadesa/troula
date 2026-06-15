<?php

namespace App\Models;

use App\Enums\EstadoPago;
use App\Enums\TipoPago;
use Database\Factories\PagoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    /** @use HasFactory<PagoFactory> */
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'reserva_id',
        'tipo',
        'importe',
        'estado',
        'pasarela',
        'referencia_pasarela',
        'pagado_en',
    ];

    protected $casts = [
        'tipo' => TipoPago::class,
        'estado' => EstadoPago::class,
        'importe' => 'decimal:2',
        'pagado_en' => 'datetime',
    ];

    /**
     * @return BelongsTo<Reserva, $this>
     */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }
}
