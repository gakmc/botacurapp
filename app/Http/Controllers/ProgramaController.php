<?php

namespace App\Http\Controllers;

use App\Programa;
use App\Servicio;
use App\Http\Requests\Programa\StoreRequest;
use App\Http\Requests\Programa\UpdateRequest;
use App\Services\WooCommerceService;
use App\Services\WooCommerceImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProgramaController extends Controller
{
    /** @var WooCommerceService */
    private $wc;

    /** @var WooCommerceImageService */
    private $wcImage;

    public function __construct(WooCommerceService $wc, WooCommerceImageService $wcImage)
    {
        $this->wc      = $wc;
        $this->wcImage = $wcImage;
    }

    public function index()
    {
        return view('themes.backoffice.pages.programa.index', [
            'programa' => Programa::activos()->get(),
        ]);
    }

    public function create()
    {
        $servicios = Servicio::all();
        return view('themes.backoffice.pages.programa.create', compact('servicios'));
    }

    public function store(StoreRequest $request, Programa $programa)
    {
        $programa = $programa->store($request);

        if (!$programa->solo_plataforma) {
            try {
                $mainImageIds = $this->uploadMainImages($request);

                $programa->loadMissing('servicios');
                $serviceUrls = $this->wcImage->getServiceImageIds($programa->servicios);

                $images = $this->wcImage->buildImagesPayload($mainImageIds, $serviceUrls);

                $wcId = $this->wc->createProduct($programa, $images);

                Programa::withoutEvents(function () use ($programa, $wcId, $mainImageIds) {
                    $programa->update([
                        'wc_product_id'     => $wcId,
                        'wc_main_image_ids' => $mainImageIds,
                    ]);
                });

            } catch (\Exception $e) {
                Log::warning("[WC-Sync] store: no se pudo sincronizar programa #{$programa->id}: " . $e->getMessage());
            }
        }

        return redirect()->route('backoffice.programa.show', $programa);
    }

    public function show(Programa $programa)
    {
        return view('themes.backoffice.pages.programa.show', [
            'programa' => $programa,
        ]);
    }

    public function edit(Programa $programa)
    {
        $this->authorize('update', $programa);
        return view('themes.backoffice.pages.programa.edit', [
            'programa'  => $programa,
            'servicios' => Servicio::all(),
        ]);
    }

    public function update(UpdateRequest $request, Programa $programa)
    {
        $programa->my_update($request);

        $fresh = $programa->fresh();

        if ($fresh->wc_product_id && !$fresh->solo_plataforma) {
            try {
                // Si se subieron nuevas imágenes principales, reemplazar las guardadas
                if ($request->hasFile('imagenes')) {
                    $mainImageIds = $this->uploadMainImages($request);
                    Programa::withoutEvents(function () use ($fresh, $mainImageIds) {
                        $fresh->update(['wc_main_image_ids' => $mainImageIds]);
                    });
                } else {
                    $mainImageIds = $fresh->wc_main_image_ids ?? [];
                }

                // Recalcular imágenes de servicios con la lista actualizada
                $fresh->loadMissing('servicios');
                $serviceUrls = $this->wcImage->getServiceImageIds($fresh->servicios);

                $images = $this->wcImage->buildImagesPayload($mainImageIds, $serviceUrls);

                $this->wc->updateProduct($fresh, $images);

            } catch (\Exception $e) {
                Log::warning("[WC-Sync] update: no se pudo sincronizar programa #{$programa->id}: " . $e->getMessage());
            }
        }

        return redirect()->route('backoffice.programa.show', $programa);
    }

    public function destroy(Programa $programa)
    {
        //
    }

    public function index_inactivos()
    {
        $programas = Programa::inactivos()->get();
        return view('themes.backoffice.pages.programa.index_inactivos', compact('programas'));
    }

    public function cambiarEstado(Request $request, Programa $programa)
    {
        $data = $request->validate([
            'estado' => 'nullable|in:activo,inactivo'
        ]);

        $programa->update(['estado' => $data['estado']]);

        // Solo actualiza estado en WC — no se tocan las imágenes (images omitido)
        try {
            $this->wc->updateProduct($programa->fresh());
        } catch (\Exception $e) {
            Log::warning("[WC-Sync] cambiarEstado: no se pudo sincronizar programa #{$programa->id}: " . $e->getMessage());
        }

        return response()->json([
            'ok'     => true,
            'estado' => $programa->estado,
            'msg'    => $programa->estado === 'activo' ? 'Programa activado' : 'Programa desactivado',
        ]);
    }

    /**
     * Busca en WooCommerce todos los programas sin wc_product_id y los vincula por nombre.
     */
    public function syncUnlinked()
    {
        $sinVincular = Programa::whereNull('wc_product_id')->get();
        $vinculados  = 0;
        $errores     = 0;

        foreach ($sinVincular as $prog) {
            try {
                $wcId = $this->wc->findByName($prog->nombre_programa);

                if ($wcId) {
                    Programa::withoutEvents(function () use ($prog, $wcId) {
                        $prog->update(['wc_product_id' => $wcId]);
                    });
                    $vinculados++;
                }
            } catch (\Exception $e) {
                Log::warning("[WC-Sync] syncUnlinked: error en programa #{$prog->id}: " . $e->getMessage());
                $errores++;
            }
        }

        $msg = "WC Sync: {$vinculados} programa(s) vinculado(s)";
        if ($errores) {
            $msg .= ", {$errores} error(es) — revisar logs.";
        }

        return redirect()->route('backoffice.programa.index')->with('status', $msg);
    }

    // ─────────────────────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────────────────────

    /**
     * Sube todas las imágenes del campo "imagenes[]" del request a WP.
     * Retorna el array de attachment IDs resultantes.
     */
    private function uploadMainImages(Request $request): array
    {
        $ids = [];

        foreach ($request->file('imagenes', []) as $file) {
            $ids[] = $this->wcImage->uploadFile($file);
        }

        return $ids;
    }
}
