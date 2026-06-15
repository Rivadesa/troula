<?php

namespace App\Models;

use App\Enums\EstadoReserva;
use App\Enums\Turno;
use Database\Factories\ReservaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Reserva extends Model
{
    /** @use HasFactory<ReservaFactory> */
    use HasFactory;

    protected $table = 'reservas';

    protected $fillable = [
        'referencia',
        'experiencia_id',
        'pack_id',
        'fecha_evento',
        'turno',
        'concello',
        'zona_id',
        'cliente_nombre',
        'cliente_email',
        'cliente_telefono',
        'lugar_evento',
        'observaciones',
        'subtotal',
        'ajuste_temporada',
        'total_complementos',
        'porte',
        'montaje',
        'total',
        'estado',
    ];

    protected $casts = [
        'fecha_evento' => 'date',
        'turno' => Turno::class,
        'estado' => EstadoReserva::class,
        'subtotal' => 'decimal:2',
        'ajuste_temporada' => 'decimal:2',
        'total_complementos' => 'decimal:2',
        'porte' => 'decimal:2',
        'montaje' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Genera una referencia única si no se ha indicado al crear.
        static::creating(function (Reserva $reserva): void {
            if (empty($reserva->referencia)) {
                $reserva->referencia = static::generarReferencia();
            }
        });
    }

    public static function generarReferencia(): string
    {
        return 'TR-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
    }

    /**
     * @return BelongsTo<Experiencia, $this>
     */
    public function experiencia(): BelongsTo
    {
        return $this->belongsTo(Experiencia::class, 'experiencia_id');
    }

    /**
     * @return BelongsTo<Pack, $this>
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class, 'pack_id');
    }

    /**
     * @return BelongsTo<ZonaPorte, $this>
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(ZonaPorte::class, 'zona_id');
    }

    /**
     * Complementos seleccionados, con precio congelado.
     *
     * @return BelongsToMany<Complemento, $this>
     */
    public function complementos(): BelongsToMany
    {
        return $this->belongsToMany(Complemento::class, 'reserva_complemento')
            ->withPivot('cantidad', 'precio_congelado')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Pago, $this>
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'reserva_id');
    }

    /**
     * Reservas que ocupan disponibilidad (estado distinto de cancelada).
     *
     * @param  Builder<Reserva>  $query
     */
    public function scopeActivas(Builder $query): void
    {
        $query->whereIn('estado', EstadoReserva::valoresActivos());
    }
}
