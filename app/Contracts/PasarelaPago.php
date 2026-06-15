<?php

namespace App\Contracts;

use App\Enums\EstadoReserva;
use App\Models\Pago;

/**
 * Punto de extensión de FASE 2 (no implementado en Fase 1).
 *
 * La integración real con Redsys (señal al reservar + cobro del saldo antes del
 * evento) implementará este contrato. En Fase 1 la tabla `pagos` y la máquina de
 * estados quedan creadas pero inertes; no hay ninguna implementación registrada.
 *
 * Flujo previsto en Fase 2:
 *   1. Al crear la reserva se genera un Pago de tipo `senal` en estado `pendiente`.
 *   2. iniciar() devuelve los datos/redirección para que el cliente pague la señal.
 *   3. confirmar() procesa la notificación de la pasarela y marca el Pago como
 *      `pagado` (o `fallido`), avanzando el estado de la reserva.
 *   4. Antes del evento se repite el flujo con un Pago de tipo `saldo`.
 *
 * @see Pago
 * @see EstadoReserva
 */
interface PasarelaPago
{
    /**
     * Identificador de la pasarela (p. ej. "redsys"). Se guarda en pagos.pasarela.
     */
    public function nombre(): string;

    /**
     * Inicia el cobro de un pago y devuelve los datos necesarios para redirigir
     * o renderizar el formulario de la pasarela.
     *
     * @return array<string, mixed>
     */
    public function iniciar(Pago $pago): array;

    /**
     * Procesa la notificación/callback de la pasarela y actualiza el estado del pago.
     *
     * @param  array<string, mixed>  $datos  Carga útil recibida de la pasarela.
     */
    public function confirmar(Pago $pago, array $datos): bool;
}
