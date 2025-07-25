<?php

namespace App\Http\Controllers;

use App\DetalleConsumo;
use App\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
            ->take(10) // Limita a los 10 programas más contratados
            ->get();


        
        // TOP 10 BEBESTIBLES
        $bebestiblesMasConsumidos = DetalleConsumo::select('id_producto', DB::raw('COUNT(*) as total'))
            ->whereHas('consumo.venta.reserva', function($q) use ($fecha) {
                $q->whereMonth('fecha_visita', $fecha->month)
                ->whereYear('fecha_visita', $fecha->year);
            })
            ->whereHas('producto.tipoProducto.sector', function($q) {
                $q->where('nombre', 'Barra'); // Solo bebestibles
            })
            ->groupBy('id_producto')
            ->orderByDesc('total')
            ->with('producto') // para obtener el nombre del producto
            ->take(10)
            ->get();


        return view('themes.backoffice.pages.informe.index', compact('programasMasContratados', 'bebestiblesMasConsumidos'));
    }
}
