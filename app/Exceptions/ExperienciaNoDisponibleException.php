<?php

namespace App\Exceptions;

use RuntimeException;

class ExperienciaNoDisponibleException extends RuntimeException
{
    public static function paraFecha(string $experiencia, string $fecha): self
    {
        return new self("La experiencia «{$experiencia}» no tiene unidades libres para el {$fecha}.");
    }
}
