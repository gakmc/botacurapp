<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WooCommerceImageService
{
    private const EXTENSIONS  = ['jpeg', 'jpg', 'png', 'webp'];
    private const MAX_PER_KEY = 20;

    /** @var Client */
    private $wpClient;

    /** @var Client */
    private $httpClient;

    /** @var string */
    private $serviceImageBaseUrl;

    public function __construct()
    {
        $storeUrl = rtrim(config('woocommerce.store_url'), '/');

        $this->serviceImageBaseUrl = rtrim(config('woocommerce.service_image_base_url'), '/');

        $this->wpClient = new Client([
            'base_uri' => $storeUrl . '/wp-json/wp/v2/',
            'auth'     => [
                config('woocommerce.wp_user'),
                config('woocommerce.wp_app_password'),
            ],
            'timeout'  => 30,
        ]);

        $this->httpClient = new Client([
            'timeout'         => 5,
            'connect_timeout' => 3,
            'http_errors'     => false,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  IMÁGENES PRINCIPALES (subidas desde el formulario)
    // ─────────────────────────────────────────────────────────────

    /**
     * Sube un archivo al Media Library de WordPress.
     * Retorna el attachment ID asignado por WP.
     */
    public function uploadFile(UploadedFile $file): int
    {
        try {
            $response = $this->wpClient->post('media', [
                'headers' => [
                    'Content-Type'        => $file->getMimeType(),
                    'Content-Disposition' => 'attachment; filename="' . $file->getClientOriginalName() . '"',
                ],
                'body' => fopen($file->getRealPath(), 'r'),
            ]);

            $data = json_decode($response->getBody(), true);

            Log::info("[WC-Images] ✓ Imagen principal subida | WP ID: {$data['id']} | {$file->getClientOriginalName()}");

            return (int) $data['id'];

        } catch (RequestException $e) {
            Log::error("[WC-Images] ✗ uploadFile falló: " . $e->getMessage());
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  IMÁGENES DE SERVICIOS (detectadas por convención de nombres)
    // ─────────────────────────────────────────────────────────────

    /**
     * Retorna los attachment IDs de WP para los servicios indicados.
     *
     * Flujo por cada URL detectada:
     *   1. ¿Está ya en WP Media Library? → reutiliza el ID existente
     *   2. No está → descarga + registra en Media Library → obtiene ID
     *   3. ID guardado en cache permanente (llave = md5 de la URL)
     *
     * Múltiples programas que comparten el mismo servicio reciben el mismo
     * attachment ID — no se crea ningún duplicado en wp5u_posts.
     */
    public function getServiceImageIds(Collection $servicios): array
    {
        $ids = [];

        foreach ($servicios as $servicio) {
            $key = $this->extractKey($servicio->slug ?? '');

            if ($key === '') {
                continue;
            }

            $urls = Cache::remember("wc_svc_probe_{$key}", 3600, function () use ($key) {
                return $this->probeImages($key);
            });

            foreach ($urls as $url) {
                $id = $this->ensureImported($url);

                if ($id !== null && !in_array($id, $ids, true)) {
                    $ids[] = $id;
                }
            }
        }

        return $ids;
    }

    /**
     * Construye el array de imágenes para el payload de WooCommerce.
     *
     * Regla: las imágenes de servicios nunca deben ser la imagen destacada.
     * WooCommerce toma el primer elemento del array como featured image,
     * por lo que los IDs de servicios SOLO se incluyen si hay al menos
     * una imagen principal que ocupe la position 0.
     *
     * Si no hay imágenes principales se retorna [] → WC conserva las
     * imágenes existentes del producto sin modificarlas.
     *
     * position 0   → imagen destacada (Product Image, viene del form)
     * position 1+  → galería (Product Gallery)
     */
    public function buildImagesPayload(array $mainImageIds, array $serviceImageIds): array
    {
        if (empty($mainImageIds)) {
            return [];
        }

        $images   = [];
        $position = 0;

        foreach ($mainImageIds as $id) {
            $images[] = ['id' => (int) $id, 'position' => $position++];
        }

        foreach ($serviceImageIds as $id) {
            $images[] = ['id' => (int) $id, 'position' => $position++];
        }

        return $images;
    }

    // ─────────────────────────────────────────────────────────────
    //  HELPERS INTERNOS
    // ─────────────────────────────────────────────────────────────

    /**
     * Garantiza que la imagen de la URL esté registrada en WP Media Library.
     * Primero busca por nombre de archivo para no importar duplicados.
     * El ID resultante queda en cache permanente.
     */
    private function ensureImported(string $url): ?int
    {
        $cacheKey = 'wc_img_id_' . md5($url);

        return Cache::rememberForever($cacheKey, function () use ($url) {
            $filename = basename(parse_url($url, PHP_URL_PATH));

            // 1. ¿Ya existe en Media Library?
            $existingId = $this->searchExistingMedia($filename);
            if ($existingId !== null) {
                Log::info("[WC-Images] Reutilizando attachment existente | WP ID: {$existingId} | {$filename}");
                return $existingId;
            }

            // 2. No existe → descargar e importar
            try {
                $response = $this->httpClient->get($url);

                if ($response->getStatusCode() !== 200) {
                    Log::warning("[WC-Images] No se pudo descargar: {$url} (HTTP {$response->getStatusCode()})");
                    return null;
                }

                $content  = (string) $response->getBody();
                $mimeType = $response->getHeaderLine('Content-Type') ?: 'image/jpeg';
                $mimeType = explode(';', $mimeType)[0]; // limpiar charset si viene adjunto

                $tmpPath = tempnam(sys_get_temp_dir(), 'wc_svc_');
                file_put_contents($tmpPath, $content);

                $wpResponse = $this->wpClient->post('media', [
                    'headers' => [
                        'Content-Type'        => $mimeType,
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                    ],
                    'body' => fopen($tmpPath, 'r'),
                ]);

                @unlink($tmpPath);

                $data = json_decode($wpResponse->getBody(), true);

                Log::info("[WC-Images] ✓ Servicio importado | WP ID: {$data['id']} | {$filename}");

                return (int) $data['id'];

            } catch (\Exception $e) {
                Log::warning("[WC-Images] ensureImported falló para {$url}: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Busca en WP Media Library si ya existe un attachment con ese nombre de archivo.
     * Retorna el ID si lo encuentra, null si no existe.
     */
    private function searchExistingMedia(string $filename): ?int
    {
        try {
            $name = pathinfo($filename, PATHINFO_FILENAME);

            $response = $this->wpClient->get('media', [
                'query' => ['search' => $name, 'per_page' => 20],
            ]);

            $items = json_decode($response->getBody(), true);

            foreach ($items as $item) {
                if (basename($item['source_url']) === $filename) {
                    return (int) $item['id'];
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::warning("[WC-Images] searchExistingMedia falló para '{$filename}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Prueba URLs {key}-N.ext secuencialmente hasta el primer 404.
     */
    private function probeImages(string $key): array
    {
        $urls = [];
        $n    = 1;

        while ($n <= self::MAX_PER_KEY) {
            $found = false;

            foreach (self::EXTENSIONS as $ext) {
                $url = "{$this->serviceImageBaseUrl}/{$key}-{$n}.{$ext}";

                if ($this->urlExists($url)) {
                    $urls[]  = $url;
                    $found   = true;
                    break;
                }
            }

            if (!$found) {
                break;
            }

            $n++;
        }

        if (!empty($urls)) {
            Log::debug("[WC-Images] Probe '{$key}': " . count($urls) . " imagen(es)");
        }

        return $urls;
    }

    private function urlExists(string $url): bool
    {
        try {
            $response = $this->httpClient->head($url);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            unset($e); // errores de conexión/timeout son esperados durante el probing
            return false;
        }
    }

    // "masaje-alivio-30-min" → "masaje" | "desayuno-u-once" → "desayuno"
    private function extractKey(string $slug): string
    {
        if ($slug === '') {
            return '';
        }

        return explode('-', $slug)[0];
    }
}
