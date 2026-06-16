<?php

namespace App\Services;

use App\Enums\TipoAjuste;
use App\Models\Complemento;
use App\Models\ConcelloZona;
use App\Models\Experiencia;
use App\Models\Pack;
use App\Models\Temporada;
use App\Models\ZonaPorte;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Motor de precios. Reutilizado por el configurador del frontend y por el backend;
 * la lógica de cálculo vive aquí y NO se duplica en JS.
 *
 * Cadena de cálculo:
 *   1. Base: precio de la experiencia, o precio cerrado del pack si se elige uno.
 *   2. Ajuste por temporada sobre la base (recargo/descuento).
 *   3. Complementos extra (override de precio por experiencia si existe).
 *   4. Porte y montaje según la zona del concello.
 *   5. Total = subtotal + ajuste + complementos + porte + montaje.
 */
class CalculadoraPrecioService
{
    /**
     * @param  array<int|string, int>  $complementos  Mapa [complemento_id => cantidad] de complementos EXTRA.
     *                                                Si hay pack, sus complementos incluidos NO van aquí
     *                                                (ya están en el precio cerrado); solo los extras.
     */
    public function calcular(
        Experiencia $experiencia,
        ?Pack $pack = null,
        array $complementos = [],
        CarbonInterface|string|null $fechaEvento = null,
        ?string $concello = null,
    ): DesglosePrecio {
        $fecha = $this->normalizarFecha($fechaEvento);

        // 1. Base
        $base = $pack !== null
            ? (float) $pack->precio
            : (float) $experiencia->precio_base;
        $subtotal = round($base, 2);

        // 2. Ajuste por temporada
        $temporada = $fecha !== null ? $this->temporadaPara($fecha) : null;
        $ajuste = $this->calcularAjuste($base, $temporada);

        // 3. Complementos extra
        [$totalComplementos, $lineas] = $this->calcularComplementos($experiencia, $complementos);

        // 4. Porte y montaje
        $zona = $concello !== null ? $this->zonaPara($concello) : null;
        $porte = $zona !== null ? round((float) $zona->precio_porte, 2) : 0.0;
        $montaje = $zona !== null ? round((float) $zona->precio_montaje, 2) : 0.0;

        // 5. Total
        $total = round($subtotal + $ajuste + $totalComplementos + $porte + $montaje, 2);

        return new DesglosePrecio(
            subtotal: $subtotal,
            ajusteTemporada: $ajuste,
            totalComplementos: round($totalComplementos, 2),
            porte: $porte,
            montaje: $montaje,
            total: $total,
            esPack: $pack !== null,
            baseNombre: $pack?->nombre ?? $experiencia->nombre,
            temporadaNombre: $temporada?->nombre,
            zonaNombre: $zona?->nombre,
            lineasComplementos: $lineas,
        );
    }

    /**
     * Localiza la temporada activa que aplica a la fecha. Los rangos se repiten
     * cada año, por lo que se compara por mes/día (MMDD) y se admite que un rango
     * cruce el fin de año (p. ej. 15-dic a 10-ene).
     */
    public function temporadaPara(CarbonInterface $fecha): ?Temporada
    {
        $objetivo = (int) $fecha->format('md');

        return Temporada::query()
            ->where('activo', true)
            ->orderBy('id')
            ->get()
            ->first(function (Temporada $temporada) use ($objetivo): bool {
                $inicio = (int) $temporada->fecha_inicio->format('md');
                $fin = (int) $temporada->fecha_fin->format('md');

                if ($inicio <= $fin) {
                    return $objetivo >= $inicio && $objetivo <= $fin;
                }

                // Rango que cruza el fin de año.
                return $objetivo >= $inicio || $objetivo <= $fin;
            });
    }

    private function calcularAjuste(float $base, ?Temporada $temporada): float
    {
        if ($temporada === null) {
            return 0.0;
        }

        $valor = (float) $temporada->valor;

        return $temporada->tipo_ajuste === TipoAjuste::Porcentaje
            ? round($base * $valor / 100, 2)
            : round($valor, 2);
    }

    /**
     * @param  array<int|string, int>  $complementos
     * @return array{0: float, 1: array<int, array{complemento_id:int, nombre:string, cantidad:int, precio_unitario:float, subtotal:float}>}
     */
    private function calcularComplementos(Experiencia $experiencia, array $complementos): array
    {
        if ($complementos === []) {
            return [0.0, []];
        }

        $experiencia->loadMissing('complementos');
        $ofrecidos = $experiencia->complementos->keyBy('id');

        $total = 0.0;
        $lineas = [];

        foreach ($complementos as $complementoId => $cantidad) {
            $complementoId = (int) $complementoId;
            $cantidad = (int) $cantidad;

            if ($cantidad <= 0) {
                continue;
            }

            /** @var Complemento|null $ofrecido */
            $ofrecido = $ofrecidos->get($complementoId);

            // Solo se aceptan complementos que la experiencia ofrece y que estén activos.
            // (Defensa frente a IDs manipulados en la petición Livewire.)
            if ($ofrecido === null || ! $ofrecido->activo) {
                continue;
            }

            // Cantidad acotada al máximo permitido por el pivote.
            $cantidad = min($cantidad, max(1, (int) $ofrecido->pivot->cantidad_maxima));

            // precio_override de la experiencia si existe; si no, precio del complemento.
            $override = $ofrecido->pivot->precio_override;
            $precioUnitario = (float) ($override ?? $ofrecido->precio);

            $subtotalLinea = round($precioUnitario * $cantidad, 2);
            $total += $subtotalLinea;

            $lineas[] = [
                'complemento_id' => $ofrecido->id,
                'nombre' => $ofrecido->nombre,
                'cantidad' => $cantidad,
                'precio_unitario' => round($precioUnitario, 2),
                'subtotal' => $subtotalLinea,
            ];
        }

        return [round($total, 2), $lineas];
    }

    public function zonaPara(string $concello): ?ZonaPorte
    {
        $mapeo = ConcelloZona::query()
            ->with('zona')
            ->whereRaw('LOWER(concello) = ?', [mb_strtolower(trim($concello))])
            ->first();

        return $mapeo?->zona;
    }

    private function normalizarFecha(CarbonInterface|string|null $fecha): ?CarbonInterface
    {
        if ($fecha === null) {
            return null;
        }

        return $fecha instanceof CarbonInterface ? $fecha : Carbon::parse($fecha);
    }
}
