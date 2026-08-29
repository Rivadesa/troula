<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Datos de la empresa. Fila única (singleton) editable desde el panel.
 * Se cachea para no consultar la BD en cada render del frontend.
 */
class Configuracion extends Model
{
    protected $table = 'configuracion';

    protected $guarded = [];

    protected $casts = [
        'mail_password' => 'encrypted',
        'mail_port' => 'integer',
        'senal_valor' => 'decimal:2',
        'pago_transferencia' => 'boolean',
        'pago_tarjeta' => 'boolean',
        'reserva_minutos_retencion' => 'integer',
        // La clave de Redsys permite firmar cobros: nunca en claro en la BD.
        'redsys_clave' => 'encrypted',
    ];

    /**
     * Señal que corresponde a un importe total, redondeada a céntimos.
     * Nunca supera el total ni baja de cero.
     */
    public function senalPara(float $total): float
    {
        $valor = (float) $this->senal_valor;

        $senal = $this->senal_tipo === 'fijo'
            ? $valor
            : $total * $valor / 100;

        return round(max(0, min($senal, $total)), 2);
    }

    /**
     * Clave de firma de Redsys ya descifrada, o null si el valor guardado no se
     * puede descifrar.
     *
     * // DECISIÓN: se traga la excepción a propósito. La columna va cifrada, y si
     * alguien la escribe a mano en la BD (phpMyAdmin, un volcado restaurado con
     * otra APP_KEY) el descifrado falla; sin esto, esa fila tumbaría con un 500
     * la página pública de pago. Así el TPV simplemente no se ofrece.
     */
    public function claveRedsys(): ?string
    {
        try {
            return $this->redsys_clave;
        } catch (\Throwable $e) {
            Log::warning('No se pudo descifrar la clave de Redsys: '.$e->getMessage());

            return null;
        }
    }

    /**
     * ¿Está Redsys operativo? Requiere el interruptor y las tres credenciales.
     */
    public function cobraConTarjeta(): bool
    {
        return (bool) $this->pago_tarjeta
            && filled($this->redsys_comercio)
            && filled($this->redsys_terminal)
            && filled($this->claveRedsys());
    }

    private const CACHE_KEY = 'configuracion.empresa';

    /**
     * Devuelve la configuración actual (la crea con valores por defecto si no existe).
     */
    public static function actual(): self
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): self => static::query()->firstOrCreate(
            ['id' => 1],
            [
                'nombre' => 'Troula Eventos',
                'eslogan' => 'Fotomatones y experiencias para tu evento',
                'email' => 'info@troula.test',
                'ciudad' => 'A Coruña',
            ],
        ));
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Aplica la configuración de correo guardada a la config en tiempo de ejecución.
     * Si el mailer es `log` (por defecto, sin SMTP), no se envía nada de verdad.
     */
    public function aplicarCorreo(): void
    {
        $mailer = $this->mail_mailer ?: 'log';
        config(['mail.default' => $mailer]);

        if ($mailer === 'smtp' && filled($this->mail_host)) {
            config([
                'mail.mailers.smtp.host' => $this->mail_host,
                'mail.mailers.smtp.port' => $this->mail_port ?: 587,
                'mail.mailers.smtp.username' => $this->mail_username,
                'mail.mailers.smtp.password' => $this->mail_password,
                'mail.mailers.smtp.encryption' => $this->mail_encryption ?: null,
                // Compatibilidad: 'ssl' → esquema smtps (puerto 465); 'tls' usa STARTTLS.
                'mail.mailers.smtp.scheme' => $this->mail_encryption === 'ssl' ? 'smtps' : null,
            ]);
        }

        if (filled($this->mail_from_address)) {
            config(['mail.from.address' => $this->mail_from_address]);
        }

        if (filled($this->mail_from_name)) {
            config(['mail.from.name' => $this->mail_from_name]);
        }

        if (filled($this->mail_admin_address)) {
            config(['mail.admin_address' => $this->mail_admin_address]);
        }
    }
}
