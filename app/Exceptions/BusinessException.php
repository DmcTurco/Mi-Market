<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Regla del negocio que impide la operación (stock insuficiente, caja cerrada...).
 * El mensaje se muestra tal cual al usuario, así que debe estar en lenguaje sencillo.
 */
class BusinessException extends RuntimeException
{
}
