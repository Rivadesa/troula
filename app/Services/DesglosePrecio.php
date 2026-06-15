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
    ) {}

    /**
     * Cifras congeladas para persistir en la tabla `reservas`.
     *
     * @return array{subtotal:float, ajuste_temporada:float, total_complementos:float, porte:float, montaje:float, total:float}
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
        ];
    }
}
