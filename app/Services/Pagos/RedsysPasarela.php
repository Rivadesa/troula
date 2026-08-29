<?php

namespace App\Services\Pagos;

use App\Contracts\PasarelaPago;
use App\Enums\EstadoPago;
use App\Models\Configuracion;
use App\Models\Pago;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * TPV Virtual de Redsys (redirección con formulario autoenviado).
 *
 * Protocolo, resumido:
 *  1. Se manda un POST al TPV con tres campos: Ds_SignatureVersion,
 *     Ds_MerchantParameters (JSON en base64 URL-safe) y Ds_Signature.
 *  2. La firma es un HMAC-SHA256 de Ds_MerchantParameters usando una clave
 *     DERIVADA: la clave del comercio (base64) cifrada en 3DES-CBC con el número
 *     de pedido como dato. Es decir, cada pedido se firma con una clave distinta.
 *  3. Redsys avisa del resultado por POST a la URL de notificación (servidor a
 *     servidor) y devuelve al cliente a URLOK/URLKO. **Lo que vale es la
 *     notificación**, y hay que verificar su firma antes de darla por buena.
 *
 * // DECISIÓN: sin librería externa. El protocolo son ~40 líneas y así no
 * dependemos de un paquete de terceros para algo que toca dinero.
 */
class RedsysPasarela implements PasarelaPago
{
    private const ENDPOINTS = [
        'pruebas' => 'https://sis-t.redsys.es:25443/sis/realizarPago',
        'produccion' => 'https://sis.redsys.es/sis/realizarPago',
    ];

    /** Códigos de respuesta 0000-0099 = autorizada. */
    private const AUTORIZADA_HASTA = 99;

    public function nombre(): string
    {
        return 'redsys';
    }

    public function endpoint(): string
    {
        $entorno = Configuracion::actual()->redsys_entorno ?: 'pruebas';

        return self::ENDPOINTS[$entorno] ?? self::ENDPOINTS['pruebas'];
    }

    /**
     * Prepara el formulario que se autoenvía al TPV.
     *
     * @return array{url:string, campos:array<string,string>}
     */
    public function iniciar(Pago $pago): array
    {
        $config = Configuracion::actual();
        $reserva = $pago->reserva;

        // Número de pedido único por INTENTO: Redsys rechaza repetirlo (SIS0051),
        // así que un reintento tras un fallo necesita uno nuevo.
        $pedido = $this->generarPedido($pago);
        $pago->update(['referencia_pasarela' => $pedido, 'pasarela' => $this->nombre()]);

        $parametros = [
            'DS_MERCHANT_AMOUNT' => (string) (int) round((float) $pago->importe * 100),
            'DS_MERCHANT_ORDER' => $pedido,
            'DS_MERCHANT_MERCHANTCODE' => $config->redsys_comercio,
            'DS_MERCHANT_CURRENCY' => '978',           // EUR
            'DS_MERCHANT_TRANSACTIONTYPE' => '0',      // autorización
            'DS_MERCHANT_TERMINAL' => $config->redsys_terminal ?: '1',
            'DS_MERCHANT_MERCHANTURL' => route('pago.notificacion'),
            'DS_MERCHANT_URLOK' => route('pago.resultado', ['reserva' => $reserva->referencia, 'estado' => 'ok']),
            'DS_MERCHANT_URLKO' => route('pago.resultado', ['reserva' => $reserva->referencia, 'estado' => 'ko']),
            'DS_MERCHANT_PRODUCTDESCRIPTION' => Str::limit('Señal reserva '.$reserva->referencia, 125, ''),
            'DS_MERCHANT_TITULAR' => Str::limit($reserva->cliente_nombre, 60, ''),
        ];

        $merchantParameters = $this->codificar($parametros);

        return [
            'url' => $this->endpoint(),
            'campos' => [
                'Ds_SignatureVersion' => 'HMAC_SHA256_V1',
                'Ds_MerchantParameters' => $merchantParameters,
                'Ds_Signature' => $this->firmar($merchantParameters, $pedido),
            ],
        ];
    }

    /**
     * Procesa la notificación del TPV. Devuelve true solo si la firma es válida
     * Y la operación fue autorizada.
     *
     * @param  array<string, mixed>  $datos  POST recibido de Redsys.
     */
    public function confirmar(Pago $pago, array $datos): bool
    {
        $merchantParameters = (string) ($datos['Ds_MerchantParameters'] ?? '');
        $firmaRecibida = (string) ($datos['Ds_Signature'] ?? '');

        if ($merchantParameters === '' || $firmaRecibida === '') {
            Log::warning('Redsys: notificación sin parámetros o sin firma', ['pago' => $pago->id]);

            return false;
        }

        $parametros = $this->decodificar($merchantParameters);
        $pedido = (string) ($parametros['Ds_Order'] ?? '');

        // La firma se valida con la clave derivada del pedido que viene en la
        // notificación; si no coincide con la del pago, algo no cuadra.
        if ($pedido === '' || $pedido !== $pago->referencia_pasarela) {
            Log::warning('Redsys: el pedido no coincide con el pago', [
                'pago' => $pago->id, 'pedido' => $pedido, 'esperado' => $pago->referencia_pasarela,
            ]);

            return false;
        }

        if (! $this->firmaValida($merchantParameters, $firmaRecibida, $pedido)) {
            Log::warning('Redsys: firma inválida en la notificación', ['pago' => $pago->id]);

            return false;
        }

        $respuesta = (int) ($parametros['Ds_Response'] ?? 9999);
        $autorizada = $respuesta >= 0 && $respuesta <= self::AUTORIZADA_HASTA;

        $pago->update([
            'estado' => $autorizada ? EstadoPago::Pagado : EstadoPago::Fallido,
            'pagado_en' => $autorizada ? now() : null,
        ]);

        return $autorizada;
    }

    // ------------------------------------------------------------------
    // Firma
    // ------------------------------------------------------------------

    /**
     * Comparación en tiempo constante para no filtrar información por el tiempo
     * de respuesta.
     */
    public function firmaValida(string $merchantParameters, string $firmaRecibida, string $pedido): bool
    {
        $esperada = $this->firmar($merchantParameters, $pedido);

        // Redsys firma las notificaciones en base64 URL-safe.
        return hash_equals($this->normalizarBase64($esperada), $this->normalizarBase64($firmaRecibida));
    }

    public function firmar(string $merchantParameters, string $pedido): string
    {
        $clave = $this->claveDelPedido($pedido);

        return base64_encode(hash_hmac('sha256', $merchantParameters, $clave, true));
    }

    /**
     * Clave específica del pedido: 3DES-CBC de la clave del comercio usando el
     * número de pedido como dato, con IV de ceros y relleno a múltiplo de 8.
     */
    private function claveDelPedido(string $pedido): string
    {
        $claveComercio = base64_decode((string) Configuracion::actual()->claveRedsys(), true);

        if ($claveComercio === false || $claveComercio === '') {
            throw new \RuntimeException('La clave de Redsys no está configurada o no es base64 válido.');
        }

        // 3DES necesita bloques de 8 bytes; Redsys rellena con \0 (ZeroPadding).
        $relleno = strlen($pedido) % 8;
        $dato = $relleno === 0 ? $pedido : $pedido.str_repeat("\0", 8 - $relleno);

        $cifrado = openssl_encrypt(
            $dato,
            'des-ede3-cbc',
            $claveComercio,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            str_repeat("\0", 8),
        );

        if ($cifrado === false) {
            throw new \RuntimeException('No se pudo derivar la clave de Redsys para el pedido '.$pedido);
        }

        return $cifrado;
    }

    // ------------------------------------------------------------------
    // Codificación
    // ------------------------------------------------------------------

    /**
     * @param  array<string, string|null>  $parametros
     */
    public function codificar(array $parametros): string
    {
        return base64_encode((string) json_encode($parametros, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    public function decodificar(string $merchantParameters): array
    {
        $json = base64_decode(strtr($merchantParameters, '-_', '+/'), false);

        return is_string($json) ? (array) json_decode($json, true) : [];
    }

    private function normalizarBase64(string $valor): string
    {
        return strtr(trim($valor), '-_', '+/');
    }

    /**
     * Pedido de 8 caracteres: 4 primeros numéricos (lo exige Redsys) + sufijo
     * aleatorio para que cada intento sea único.
     */
    private function generarPedido(Pago $pago): string
    {
        return str_pad((string) ($pago->id % 10000), 4, '0', STR_PAD_LEFT)
            .Str::upper(Str::random(4));
    }
}
