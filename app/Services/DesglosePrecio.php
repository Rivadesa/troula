<?php

namespace App\Services;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Desglose estructurado que devuelve la CalculadoraPrecioService.
 *
 * Las 6 cifras principales (subtotal, ajuste_temporada, total_complementos,
 * porte, montaje, total) se guardan congeladas en la reserva; el resto de
 * campos son para mostrar el detalle en el resumen del configurador.
 *
 * @implements Arrayable<string, mixed>
 */
final class DesglosePrecio implements Arrayable
{
    /**
     * @param  array<int, array{complemento_id:int, nombre:string, cantidad:int, precio_unitario:float, subtotal:float}>  $lineasComplementos
     * @param  array<int, array{complemento_id:int, nombre:string, cantidad:int}>  $lineasAConsultar  Complementos de precio variable: se muestran pero NO suman.
     */
    public function __construct(
        public readonly float $subtotal,
        public readonly float $ajusteTemporada,
        public readonly float $totalComplementos,
        public readonly float $porte,
        public readonly float $montaje,
        public readonly float $total,
        public readonly bool $esPack = false,
        public readonly ?string $baseNombre = null,
        public readonly ?string $temporadaNombre = null,
        public readonly ?string $zonaNombre = null,
        public readonly array $lineasComplementos = [],
        public readonly int $horasExtra = 0,
        public readonly float $importeHorasExtra = 0.0,
        public readonly array $lineasAConsultar = [],
        public readonly float $suplementoBase = 0.0,
    ) {}

    /**
     * ¿Hay alguna línea pendiente de presupuestar? El configurador lo usa para
     * avisar de que el total mostrado no es definitivo.
     */
    public function tieneLineasAConsultar(): bool
    {
        return $this->lineasAConsultar !== [];
    }

    /**
     * Cifras congeladas para persistir en la tabla `reservas`.
     *
     * @return array{subtotal:float, ajuste_temporada:float, total_complementos:float, porte:float, montaje:float, total:float, horas_extra:int}
     */
    public function paraReserva(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'ajuste_temporada' => $this->ajusteTemporada,
            'total_complementos' => $this->totalComplementos,
            'porte' => $this->porte,
            'montaje' => $this->montaje,
            'total' => $this->total,
            'horas_extra' => $this->horasExtra,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ...$this->paraReserva(),
            'es_pack' => $this->esPack,
            'base_nombre' => $this->baseNombre,
            'temporada_nombre' => $this->temporadaNombre,
            'zona_nombre' => $this->zonaNombre,
            'lineas_complementos' => $this->lineasComplementos,
            'importe_horas_extra' => $this->importeHorasExtra,
            'lineas_a_consultar' => $this->lineasAConsultar,
            'suplemento_base' => $this->suplementoBase,
        ];
    }
}
