<?php

namespace App\Http\Controllers;

use App\FechaDisponible;
use App\Services\WpCacheService;
use Illuminate\Http\Request;

class CalendarioController extends Controller
{
    /** @var WpCacheService */
    private $wpCache;

    public function __construct(WpCacheService $wpCache)
    {
        $this->wpCache = $wpCache;
    }
    public function index()
    {
        FechaDisponible::sincronizarRegulares(120);

        return view('themes.backoffice.pages.admin.disponibilidad.calendario');
    }

    public function eventos()
    {
        $fechas = FechaDisponible::where('fecha', '>=', now()->toDateString())
            ->orderBy('fecha')
            ->get();

        $eventos = $fechas->map(function ($f) {
            if (!$f->habilitada) {
                $color = '#EF5350';
            } elseif ($f->tipo === 'festivo') {
                $color = '#FF9800';
            } else {
                $color = '#66BB6A';
            }

            return [
                'id'    => $f->id,
                'title' => $f->tipo === 'festivo' ? 'Festivo' : ($f->habilitada ? '✓' : '✗'),
                'start' => $f->fecha->format('Y-m-d'),
                'allDay' => true,
                'color' => $color,
                'extendedProps' => [
                    'tipo'      => $f->tipo,
                    'habilitada' => $f->habilitada,
                    'nota'      => $f->nota,
                    'fechaId'   => $f->id,
                ],
            ];
        });

        return response()->json($eventos);
    }

    public function toggle(Request $request, FechaDisponible $fecha)
    {
        $fecha->update(['habilitada' => !$fecha->habilitada]);
        $this->wpCache->clearFechasCache();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'habilitada' => $fecha->fresh()->habilitada]);
        }

        return back()->with('success', 'Fecha actualizada.');
    }

    public function agregarFestivo(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date|after:today',
            'nota'  => 'nullable|string|max:255',
        ]);

        FechaDisponible::updateOrCreate(
            ['fecha' => $request->fecha],
            [
                'tipo'       => $request->tipo,
                'habilitada' => true,
                'nota'       => $request->nota,
            ]
        );

        $this->wpCache->clearFechasCache();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Festivo agregado.');
    }

    public function eliminar(Request $request, FechaDisponible $fecha)
    {
        $fecha->delete();
        $this->wpCache->clearFechasCache();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Fecha eliminada.');
    }
}
