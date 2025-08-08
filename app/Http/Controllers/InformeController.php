<?php
namespace App\Http\Controllers;

use App\DetalleConsumo;
use App\DetalleVentaDirecta;
use App\Producto;
use App\Reserva;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InformeController extends Controller
{
    public function index()
    {
        $fecha = Carbon::now();

        $programasMasContratados = Reserva::select('id_programa', DB::raw('COUNT(*) as total'))
            ->whereMonth('fecha_visita', $fecha->month)
            ->whereYear('fecha_visita', $fecha->year)
            ->groupBy('id_programa')
            ->orderByDesc('total')
            ->with('programa') // Asegúrate de tener la relación definida
            ->take(10)         // Limita a los 10 programas más contratados
            ->get();

        // TOP 10 BEBESTIBLES
        $bebestiblesConsumo = DetalleConsumo::select('id_producto', DB::raw('SUM(cantidad_producto) as total'))
            ->whereHas('consumo.venta.reserva', function ($q) use ($fecha) {
                $q->whereMonth('fecha_visita', $fecha->month)
                    ->whereYear('fecha_visita', $fecha->year);
            })
            ->whereHas('producto.tipoProducto.sector', function ($q) {
                $q->where('nombre', 'Barra'); // Solo bebestibles
            })
            ->groupBy('id_producto')
            ->with('producto') // para obtener el nombre del producto
            ->pluck('total', 'id_producto');

        $bebestiblesDirectas = DetalleVentaDirecta::select('producto_id', DB::raw('sum(cantidad) as total'))
            ->whereHas('ventaDirecta', function ($q) use ($fecha) {
                $q->whereMonth('fecha', $fecha->month)
                    ->whereYear('fecha', $fecha->year);
            })
            ->whereHas('producto.tipoProducto.sector', function ($q) {
                $q->where('nombre', 'barra');
            })
            ->groupBy('producto_id')
            ->pluck('total', 'producto_id');

        // $totales = $bebestiblesConsumo->mergeRecursive($bebestiblesDirectas)->map(function ($item) {
        //     return is_array($item) ? array_sum($item) : $item;
        // });

        $totales = collect();

        // Sumar los valores de bebestiblesConsumo
        foreach ($bebestiblesConsumo as $key => $value) {
            $totales[$key] = ($totales[$key] ?? 0) + $value;
        }

        // Sumar los valores de bebestiblesDirectas
        foreach ($bebestiblesDirectas as $key => $value) {
            $totales[$key] = ($totales[$key] ?? 0) + $value;
        }

        // Ordenar y tomar top 10
        $idsOrdenados = $totales->sortByDesc(function ($item) {
            return $item;
        })->take(10)->keys();

        // $idsOrdenados = collect($totales)->sortByDesc(function ($item) {
        //     return $item;
        // })->take(10)->keys();

        // dd($bebestiblesDirectas,$totales, $idsOrdenados);

        $bebestiblesMasConsumidos = Producto::whereIn('id', $idsOrdenados)
            ->get()
            ->map(function ($producto) use ($totales) {
                $producto->total = $totales[$producto->id] ?? 0;
                return $producto;
            })
            ->sortByDesc('total')
            ->values();

        return view('themes.backoffice.pages.informe.index', compact('programasMasContratados', 'bebestiblesMasConsumidos'));
    }

    public function bebestiblesMensuales()
    {
        $anio = Carbon::now()->year;

        // Consumos mensuales
        $consumo = DetalleConsumo::selectRaw('MONTH(reservas.fecha_visita) as mes, SUM(cantidad_producto) as total')
            ->whereHas('consumo.venta.reserva', function ($q) use ($anio) {
                $q->whereYear('fecha_visita', $anio);
            })
            ->whereHas('producto.tipoProducto.sector', function ($q) {
                $q->where('nombre', 'Barra');
            })
            ->join('consumos', 'detalles_consumos.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->groupBy('mes')
            ->pluck('total', 'mes');

        // Ventas directas mensuales
        $directas = DetalleVentaDirecta::selectRaw('MONTH(ventas_directas.fecha) as mes, SUM(cantidad) as total')
            ->whereHas('ventaDirecta', function ($q) use ($anio) {
                $q->whereYear('fecha', $anio);
            })
            ->whereHas('producto.tipoProducto.sector', function ($q) {
                $q->where('nombre', 'Barra');
            })
            ->join('ventas_directas', 'detalles_ventas_directas.venta_directa_id', '=', 'ventas_directas.id')
            ->groupBy('mes')
            ->pluck('total', 'mes');

        // Combinar ambos resultados
        $totales = collect();
        foreach ($consumo as $mes => $valor) {
            $totales[$mes] = ($totales[$mes] ?? 0) + $valor;
        }
        foreach ($directas as $mes => $valor) {
            $totales[$mes] = ($totales[$mes] ?? 0) + $valor;
        }

        // Preparar labels y valores
        $labels  = [];
        $valores = [];
        for ($i = 1; $i <= 12; $i++) {
            $labels[]  = ucfirst(Carbon::create()->month($i)->translatedFormat('F'));
            $valores[] = $totales[$i] ?? 0;
        }

    //                 dd([
    //     'consumo' => $consumo,
    //     'directas' => $directas,
    //     'totales' => $totales
    // ]);
        return response()->json([
            'labels' => $labels,
            'data'   => $valores,
        ]);
    }


    public function programasMensuales()
    {
        $anio = Carbon::now()->year;

        $programas = Reserva::selectRaw('MONTH(fecha_visita) as mes, COUNT(*) as total')
            ->whereYear('fecha_visita', $anio)
            ->groupBy('mes')
            ->pluck('total', 'mes');



        // Combinar ambos resultados
        $totales = collect();
        foreach ($programas as $mes => $valor) {
            $totales[$mes] = ($totales[$mes] ?? 0) + $valor;
        }


        // Preparar labels y valores
        $labels  = [];
        $valores = [];
        for ($i = 1; $i <= 12; $i++) {
            $labels[]  = ucfirst(Carbon::create()->month($i)->translatedFormat('F'));
            $valores[] = $totales[$i] ?? 0;
        }

    //                 dd([
    //     'programas' => $programas,
    //     'directas' => $directas,
    //     'totales' => $totales
    // ]);
        return response()->json([
            'labels' => $labels,
            'data'   => $valores,
        ]);
    }

}
