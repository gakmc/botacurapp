<?php

namespace App\Http\Controllers\Api;

use App\Cotizacion;
use App\CotizacionItem;
use App\Http\Controllers\Controller;
use App\Producto;
use App\Programa;
use App\Servicio;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class CotizadorController extends Controller
{
    // tipo => [modelo, campo de valor]
    private $tipos = [
        'programa' => [Programa::class, 'valor_programa'],
        'servicio' => [Servicio::class, 'valor_servicio'],
        'producto' => [Producto::class, 'valor'],
    ];

    public function catalogo()
    {
        return response()->json([
            'programas' => Programa::orderBy('id')->get(['id', 'nombre_programa', 'valor_programa']),
            'servicios' => Servicio::orderBy('id')->get(['id', 'nombre_servicio', 'valor_servicio']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente' => 'required|string|max:255',
            'solicitante' => 'required|string|max:255',
            'correo' => 'nullable|email',
            'fecha_reserva' => 'required|date',
            'validez_dias' => 'nullable|integer|min:1|max:90',
            'observaciones' => 'nullable|string|max:2000',
            'incluir_pdf' => 'nullable|boolean',
            'incluir_catalogo' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.tipo' => 'required|in:programa,servicio,producto',
            'items.*.id' => 'required|integer',
            'items.*.cantidad' => 'required|integer|min:1|max:1000',
            'items.*.valor_unitario' => 'nullable|integer|min:0',
        ]);

        $cotizacion = DB::transaction(function () use ($data) {
            $cotizacion = Cotizacion::create([
                'cliente' => $data['cliente'],
                'solicitante' => $data['solicitante'],
                'correo' => isset($data['correo']) ? $data['correo'] : null,
                'fecha_emision' => Carbon::now(),
                'fecha_reserva' => Carbon::parse($data['fecha_reserva'])->format('Y-m-d'),
                'validez_dias' => isset($data['validez_dias']) ? $data['validez_dias'] : 15,
                'observaciones' => isset($data['observaciones']) ? $data['observaciones'] : null,
                'iva_incluido' => true,
            ]);

            foreach ($data['items'] as $linea) {
                list($modelo, $campoValor) = $this->tipos[$linea['tipo']];
                $item = $modelo::findOrFail($linea['id']);
                $cantidad = (int) $linea['cantidad'];
                $unitario = isset($linea['valor_unitario'])
                    ? (int) $linea['valor_unitario']
                    : (int) $item->{$campoValor};

                CotizacionItem::create([
                    'cotizacion_id' => $cotizacion->id,
                    'itemable_id' => $item->id,
                    'itemable_type' => $modelo,
                    'cantidad' => $cantidad,
                    'valor_neto' => $unitario,
                    'total' => $unitario * $cantidad,
                ]);
            }

            return $cotizacion;
        });

        $respuesta = $this->resumen($cotizacion);
        if (!empty($data['incluir_pdf'])) {
            $respuesta['pdf_base64'] = base64_encode($this->generarPdf($cotizacion)->output());
        }

        if (!empty($data['incluir_catalogo'])) {
            $ruta = storage_path('app/catalogo/Catalogo_Botacura.pdf');
            if (is_file($ruta)) {
                $respuesta['catalogo_base64'] = base64_encode(file_get_contents($ruta));
                $respuesta['catalogo_archivo'] = 'Catalogo_Botacura.pdf';
            }
        }

        return response()->json($respuesta, 201);
    }

    public function pdf($id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        return $this->generarPdf($cotizacion)->stream('cotizacion_' . $cotizacion->id . '.pdf');
    }

    public function pdfPublico($id)
    {
        return $this->pdf($id);
    }

    // Próximos días habilitados con turnos de tinaja y espacios libres (para la tarea de correos)
    public function calendario(Request $request)
    {
        $dias = min(120, max(1, (int) $request->query('dias', 60)));
        $svc  = app(\App\Services\DisponibilidadService::class);
        $hoy  = Carbon::today();

        $habilitadas = \App\FechaDisponible::where('habilitada', true)
            ->whereBetween('fecha', [$hoy->toDateString(), $hoy->copy()->addDays($dias)->toDateString() . ' 23:59:59'])
            ->pluck('fecha')
            ->map(function ($f) { return Carbon::parse($f)->toDateString(); })
            ->all();

        $salida = [];
        for ($i = 0; $i <= $dias; $i++) {
            $f     = $hoy->copy()->addDays($i);
            $fecha = $f->toDateString();
            if (!in_array($fecha, $habilitadas, true)) {
                continue;
            }
            $t   = $svc->slotsTinaja($fecha);
            $cap = $t['exclusivo'] ? ['estacion' => 0, 'terraza' => 0, 'reposera' => 0] : $svc->capacidadLibre($fecha);

            $salida[] = [
                'fecha'                    => $fecha,
                'dia'                      => $f->locale('es')->isoFormat('ddd D [de] MMM'),
                'exclusivo_agendado'       => $t['exclusivo'],
                'libre_para_uso_exclusivo' => !$t['exclusivo'] && $t['usados'] === 0,
                'tinaja_turnos_libres'     => $t['disponibles'],
                'tinaja_personas_max'      => $t['disponibles'] * 5,
                'personas_libres'          => $cap,
            ];
        }

        return response()->json(['generado' => Carbon::now()->toIso8601String(), 'dias' => $salida]);
    }

    private function generarPdf(Cotizacion $cotizacion)
    {
        $cotizacion->load('items.itemable');
        $emitida = $cotizacion->fecha_emision->isoFormat('D [de] MMMM');
        $reserva = $cotizacion->fecha_reserva->isoFormat('D [de] MMMM');
        return Pdf::loadView('pdf.cotizacion.viewPDF', compact('cotizacion', 'emitida', 'reserva'));
    }

    private function resumen(Cotizacion $cotizacion)
    {
        $cotizacion->load('items.itemable');
        $total = (int) $cotizacion->items->sum('total');
        $neto = (int) round($total / 1.19);

        return [
            'id' => $cotizacion->id,
            'archivo' => 'Cotizacion_Botacura_' . $cotizacion->id . '.pdf',
            'iva_incluido' => true,
            'neto' => $neto,
            'iva' => $total - $neto,
            'total' => $total,
            'items' => $cotizacion->items->map(function ($i) {
                return [
                    'descripcion' => $i->itemable->nombre_programa ?? $i->itemable->nombre_servicio ?? $i->itemable->nombre,
                    'cantidad' => $i->cantidad,
                    'valor_unitario' => $i->valor_neto,
                    'total' => $i->total,
                ];
            }),
            'pdf_url' => url('/api/cotizador/cotizaciones/' . $cotizacion->id . '/pdf'),
            'pdf_link' => URL::temporarySignedRoute('cotizador.publico', now()->addDays(30), ['id' => $cotizacion->id]),
        ];
    }
}
