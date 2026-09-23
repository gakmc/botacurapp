<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Se lanza cuando WooCommerce responde que un wc_product_id ya no existe
 * (404 / woocommerce_rest_product_invalid_id), a diferencia de cualquier
 * otro fallo de red/autenticación que sí debe seguir tratándose como error
 * genérico de sincronización.
 */
class WooCommerceProductNotFoundException extends RuntimeException
{
    /** @var int */
    public $wcProductId;

    public function __construct(int $wcProductId, Throwable $previous = null)
    {
        parent::__construct("Producto WooCommerce #{$wcProductId} ya no existe (404).", 0, $previous);

        $this->wcProductId = $wcProductId;
    }
}
