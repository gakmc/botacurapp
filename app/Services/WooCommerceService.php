<?php

namespace App\Services;

use App\Programa;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class WooCommerceService
{
    private const SPA_DAY_CATEGORY_ID = 71;

    /** @var Client */
    private $client;

    /** @var ProgramaContentBuilder */
    private $builder;

    public function __construct(ProgramaContentBuilder $builder)
    {
        $storeUrl = rtrim(config('woocommerce.store_url'), '/');

        $this->builder = $builder;

        $this->client = new Client([
            'base_uri' => $storeUrl . '/wp-json/wc/v3/',
            'auth'     => [
                config('woocommerce.consumer_key'),
                config('woocommerce.consumer_secret'),
            ],
            'headers' => ['Accept' => 'application/json'],
            'timeout' => 15,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  OPERACIONES PRINCIPALES
    // ─────────────────────────────────────────────────────────────

    /**
     * Crea el producto en WooCommerce y retorna el wc_product_id asignado.
     * $images: array generado por WooCommerceImageService::buildImagesPayload()
     */
    public function createProduct(Programa $programa, array $images = []): int
    {
        try {
            $response = $this->client->post('products', [
                'json' => $this->buildPayload($programa, $images),
            ]);

            $data = json_decode($response->getBody(), true);

            Log::info("[WC-Sync] ✓ Producto creado | ID WC: {$data['id']} | {$data['name']}");

            return (int) $data['id'];

        } catch (RequestException $e) {
            $this->logError('createProduct', $programa, $e);
            throw $e;
        }
    }

    /**
     * Actualiza el producto en WC.
     * Si $images está vacío, no se incluye la clave "images" en el payload
     * y WC conserva las imágenes existentes intactas.
     * No hace nada si el programa no tiene wc_product_id.
     */
    public function updateProduct(Programa $programa, array $images = []): void
    {
        if (!$programa->wc_product_id) {
            Log::warning("[WC-Sync] updateProduct ignorado: programa #{$programa->id} sin wc_product_id");
            return;
        }

        try {
            $this->client->put("products/{$programa->wc_product_id}", [
                'json' => $this->buildPayload($programa, $images),
            ]);

            Log::info("[WC-Sync] ✓ Producto actualizado | ID WC: {$programa->wc_product_id} | {$programa->nombre_programa}");

        } catch (RequestException $e) {
            $this->logError('updateProduct', $programa, $e);
            throw $e;
        }
    }

    /**
     * Obtiene los datos actuales de un producto en WC.
     */
    public function getProduct(int $wcProductId): array
    {
        $response = $this->client->get("products/{$wcProductId}");
        return json_decode($response->getBody(), true);
    }

    /**
     * Busca un producto en WC por nombre exacto (case-insensitive).
     * Retorna el wc_product_id si lo encuentra, null si no existe.
     */
    public function findByName(string $nombre): ?int
    {
        try {
            $response = $this->client->get('products', [
                'query' => [
                    'search'   => $nombre,
                    'per_page' => 20,
                    'status'   => 'any',
                ],
            ]);

            $products = json_decode($response->getBody(), true);

            foreach ($products as $product) {
                if (strtoupper($product['name']) === strtoupper($nombre)) {
                    return (int) $product['id'];
                }
            }

            return null;

        } catch (RequestException $e) {
            Log::error("[WC-Sync] findByName falló para '{$nombre}': " . $e->getMessage());
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  CONSTRUCCIÓN DEL PAYLOAD
    // ─────────────────────────────────────────────────────────────

    private function buildPayload(Programa $programa, array $images = []): array
    {
        $payload = [
            'name'          => strtoupper($programa->nombre_programa),
            'type'          => 'simple',
            'status'        => $this->resolveStatus($programa),
            'description'   => $this->builder->build($programa),
            'regular_price' => (string) $programa->valor_programa,
            'sku'           => $programa->slug,
            'categories'    => [['id' => self::SPA_DAY_CATEGORY_ID]],
        ];

        if (!empty($images)) {
            $payload['images'] = $images;
        }

        return $payload;
    }

    /**
     * null o 'activo' → publish  |  'inactivo' → draft
     */
    private function resolveStatus(Programa $programa): string
    {
        return $programa->estado === 'inactivo' ? 'draft' : 'publish';
    }

    // ─────────────────────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────────────────────

    private function logError(string $metodo, Programa $programa, RequestException $e): void
    {
        $body = $e->hasResponse()
            ? (string) $e->getResponse()->getBody()
            : 'sin respuesta';

        Log::error("[WC-Sync] ✗ {$metodo} falló | Programa #{$programa->id} | " . $e->getMessage(), [
            'response' => $body,
        ]);
    }
}
