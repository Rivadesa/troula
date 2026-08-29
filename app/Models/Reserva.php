<?php

namespace App\Models;

use App\Enums\EstadoPago;
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
        'cliente_id',
        'experiencia_id',
        'pack_id',
        'fecha_evento',
        'turno',
        'horas_extra',
        'concello',
        'zona_id',
        'cliente_nombre',
        'cliente_email',
        'cliente_telefono',
        'cliente_dni',
        'cliente_direccion',
        'contrato_texto',
        'contrato_hash',
        'contrato_aceptado_en',
        'contrato_ip',
        'contrato_user_agent',
        'lugar_evento',
        'observaciones',
        'subtotal',
        'ajuste_temporada',
        'total_complementos',
        'porte',
        'montaje',
        'total',
        'estado',
        'reserva_expira_en',
    ];

    protected $casts = [
        'fecha_evento' => 'date',
        'turno' => Turno::class,
        'horas_extra' => 'integer',
        'contrato_aceptado_en' => 'datetime',
        'reserva_expira_en' => 'datetime',
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
     * @return BelongsTo<Cliente, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
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
     * ¿El cliente ya ha aceptado el contrato?
     */
    public function contratoFirmado(): bool
    {
        return $this->contrato_aceptado_en !== null;
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

    /**
     * Reservas que de verdad bloquean el equipo en una fecha.
     *
     * Una reserva `solicitada` solo retiene la fecha hasta `reserva_expira_en`:
     * si el cliente no completa el pago, pasado ese plazo el equipo vuelve a
     * estar libre. Sin esto, cualquiera que abriese el configurador y lo
     * abandonase dejaría la fecha muerta para siempre.
     *
     * // DECISIÓN: el filtro se aplica en la CONSULTA, no en una tarea
     * programada, porque en este hosting no hay cron. `reservas:caducar` solo
     * ordena después lo que la consulta ya ignora.
     *
     * @param  Builder<Reserva>  $query
     */
    public function scopeOcupanDisponibilidad(Builder $query): void
    {
        $query->whereIn('estado', EstadoReserva::valoresActivos())
            ->where(function (Builder $q): void {
                $q->where('estado', '!=', EstadoReserva::Solicitada->value)
                    ->orWhereNull('reserva_expira_en')
                    ->orWhere('reserva_expira_en', '>', now());
            });
    }

    /**
     * ¿Es una reserva sin pagar cuya retención ya ha vencido?
     */
    public function retencionCaducada(): bool
    {
        return $this->estado === EstadoReserva::Solicitada
            && $this->reserva_expira_en !== null
            && $this->reserva_expira_en->isPast();
    }

    /**
     * ¿Se puede anular sin más? Solo mientras no haya dinero de por medio.
     */
    public function anulablePorElCliente(): bool
    {
        return $this->estado === EstadoReserva::Solicitada
            && ! $this->pagos()->where('estado', EstadoPago::Pagado)->exists();
    }
}
