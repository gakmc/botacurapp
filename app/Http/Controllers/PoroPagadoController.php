<?php

namespace App\Http\Controllers;

use App\PoroPagado;
use App\PoroPoroVenta;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PoroPagadoController extends Controller
{

    public function index(Request $request)
    {
        // Mes y año actuales o dados por input
        $mes = $request->input('mes', now()->month);
        $anio = $request->input('anio', now()->year);

        // Obtener ID del usuario
        $usuario = User::where('name', 'Camila')
            ->where('email', 'camila@botacura.cl')
            ->first(); // Solo uno

        // Todas las ventas de Camila en el mes
        $poroVentasMes = PoroPoroVenta::whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->get();

        // Rangos de pagos registrados
        $rangosPagados = PoroPagado::all();

        // Clasificar ventas en pagadas y no pagadas
        $ventasPagadas = collect();
        $ventasNoPagadas = collect();

        foreach ($poroVentasMes as $venta) {
            $fechaVenta = Carbon::parse($venta->fecha);

            $estaPagada = $rangosPagados->contains(function ($pago) use ($fechaVenta) {
                return $fechaVenta->between(
                    Carbon::parse($pago->semana_inicio),
                    Carbon::parse($pago->semana_fin)
                );
            });

            if ($estaPagada) {
                $ventasPagadas->push($venta);
            } else {
                $ventasNoPagadas->push($venta);
            }
        }


        return view('themes.backoffice.pages.poroporo.pagado.index', compact(
            'usuario', 'ventasPagadas', 'ventasNoPagadas', 'mes', 'anio'
        ));
    }


    public function create()
    {
        //
    }


    public function store(Request $request)
    {
        $data = json_decode($request->datos, true);

        PoroPagado::create([
            'semana_inicio' => $data['inicio_semana'],
            'semana_fin' => $data['fin_semana'],
            'fecha_pago' => Carbon::now()->format('Y-m-d'),
            'monto' => $data['monto'],
        ]);

        return back()->with('success','Se registro pago semanal de Poro Poro.');
    }


    public function show($id)
    {
        //
    }


    public function edit($id)
    {
        //
    }


    public function update(Request $request, $id)
    {
        //
    }


    public function destroy($id)
    {
        //
    }
}
