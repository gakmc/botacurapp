<?php

namespace App\Http\Controllers;

use App\Programa;
use App\Servicio;
use App\Exceptions\WooCommerceProductNotFoundException;
use App\Http\Requests\Programa\StoreRequest;
use App\Http\Requests\Programa\UpdateRequest;
use App\Services\WooCommerceService;
use App\Services\WooCommerceImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;

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

    public function index(Request $request)
    {
        $query = trim($request->get('search', ''));

        $programas = Programa::where(function ($q) {
                $q->activos();
            })
            ->when($query !== '', function ($q) use ($query) {
                $q->where('nombre_programa', 'LIKE', '%' . $query . '%');
            })
            ->orderBy('id', 'asc');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(
                $programas->limit(8)->get(['id', 'nombre_programa', 'valor_programa'])
            );
        }

        return view('themes.backoffice.pages.programa.index', [
            'programa' => $programas->get(),
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

        if (!$this->syncToWc($programa, $request)) {
            Alert::warning('Guardado con advertencia', 'El programa se guardó, pero no se pudo sincronizar con WooCommerce. Revisa los logs e inténtalo de nuevo desde "Editar".')->showConfirmButton();
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

        if (!$this->syncToWc($fresh, $request)) {
            Alert::warning('Guardado con advertencia', 'El programa se actualizó, pero no se pudo sincronizar con WooCommerce. Revisa los logs e inténtalo de nuevo.')->showConfirmButton();
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
        if (!$programa->solo_plataforma) {
            try {
                $this->wc->updateProduct($programa->fresh());
            } catch (WooCommerceProductNotFoundException $e) {
                Log::warning("[WC-Sync] cambiarEstado: producto WC #{$e->wcProductId} ya no existe, se desvincula el programa #{$programa->id}. Hay que reeditarlo para recrearlo en WC.");
                Programa::withoutEvents(function () use ($programa) {
                    $programa->update(['wc_product_id' => null]);
                });
            } catch (\Exception $e) {
                Log::warning("[WC-Sync] cambiarEstado: no se pudo sincronizar programa #{$programa->id}: " . $e->getMessage());
            }
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
        $sinVincular = Programa::whereNull('wc_product_id')
            ->where('solo_plataforma', false)
            ->get();
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

        $msg = "WC Sincronizado: {$vinculados} programa(s) vinculado(s)";
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

    /**
     * Sincroniza el programa con WooCommerce: actualiza el producto si ya
     * está vinculado (wc_product_id), o lo crea si todavía no existe allá
     * (por ejemplo porque el create original falló por falta de credenciales).
     *
     * Antes de crear, busca por nombre en WC para no generar un producto
     * duplicado si ya existiera del lado de WC por alguna otra vía; si lo
     * encuentra, solo vincula el ID (igual que syncUnlinked) en vez de crear
     * uno nuevo.
     *
     * Devuelve false si algo falló al hablar con WooCommerce, para que el
     * llamador pueda avisarle al usuario en vez de mostrar éxito a ciegas.
     */
    private function syncToWc(Programa $programa, Request $request): bool
    {
        if ($programa->solo_plataforma) {
            if ($programa->wc_product_id) {
                try {
                    $this->wc->draftProduct($programa);
                } catch (WooCommerceProductNotFoundException $e) {
                    Log::info("[WC-Sync] syncToWc: producto WC #{$e->wcProductId} ya no existe, se desvincula el programa #{$programa->id} (solo_plataforma, nada que pasar a borrador)");
                    Programa::withoutEvents(function () use ($programa) {
                        $programa->update(['wc_product_id' => null]);
                    });
                } catch (\Exception $e) {
                    Log::warning("[WC-Sync] syncToWc: no se pudo pasar a borrador el programa #{$programa->id}: " . $e->getMessage());
                    return false;
                }
            }
            return true;
        }

        try {
            if ($request->hasFile('imagenes')) {
                $mainImageIds = $this->uploadMainImages($request);
                Programa::withoutEvents(function () use ($programa, $mainImageIds) {
                    $programa->update(['wc_main_image_ids' => $mainImageIds]);
                });
            } else {
                $mainImageIds = $programa->wc_main_image_ids ?? [];
            }

            $programa->loadMissing('servicios');
            $serviceUrls = $this->wcImage->getServiceImageIds($programa->servicios);

            if ($programa->wc_product_id) {
                try {
                    $images = $this->wcImage->buildImagesPayload($mainImageIds, $serviceUrls);
                    $this->wc->updateProduct($programa, $images);
                    return true;
                } catch (WooCommerceProductNotFoundException $e) {
                    Log::warning("[WC-Sync] syncToWc: producto WC #{$e->wcProductId} ya no existe, se desvincula el programa #{$programa->id} y se intenta re-crear");
                    Programa::withoutEvents(function () use ($programa) {
                        $programa->update(['wc_product_id' => null]);
                    });
                }
            }

            $wcId = $this->wc->findByName($programa->nombre_programa);

            if ($wcId) {
                Programa::withoutEvents(function () use ($programa, $wcId) {
                    $programa->update(['wc_product_id' => $wcId]);
                });
                Log::info("[WC-Sync] syncToWc: programa #{$programa->id} vinculado a producto WC existente #{$wcId}, no se creó uno nuevo");
                return true;
            }

            // Sin imágenes principales, se permite que las de servicios ocupen
            // la posición 0 para que el producto no se cree sin ninguna imagen.
            $images = $this->wcImage->buildImagesPayload($mainImageIds, $serviceUrls, true);
            $wcId   = $this->wc->createProduct($programa, $images);

            Programa::withoutEvents(function () use ($programa, $wcId, $mainImageIds) {
                $programa->update([
                    'wc_product_id'     => $wcId,
                    'wc_main_image_ids' => $mainImageIds,
                ]);
            });

            return true;

        } catch (\Exception $e) {
            Log::warning("[WC-Sync] syncToWc: no se pudo sincronizar programa #{$programa->id}: " . $e->getMessage());
            return false;
        }
    }
}
