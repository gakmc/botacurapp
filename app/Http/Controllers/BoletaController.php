<?php

namespace App\Http\Controllers;

use App\Reserva;
use App\Consumo;
use App\PoroPoro;
use App\PoroPoroVenta;
use App\VentaDirecta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoletaController extends Controller
{

    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
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

    public function databoleta(Request $request ,Reserva $reserva)
    {

        $reserva->load('venta.consumo','venta.consumo.detallesConsumos.producto', 'venta.consumo.detalleServiciosExtra.servicio', 'venta.consumo.detalleServiciosExtra.precioTipoMasaje');

        $total   = 0;
        $consumo        = $reserva->venta->consumo;
        $listaConsumos = [];
        $listaServicios = [];
        $idConsumo       = null;
        $cantidadPropina = null;


        if (isset($consumo)) {
            foreach ($consumo->detallesConsumos as $detalles) {
                $listaConsumos[] = $detalles;
            }


            foreach($consumo->detalleServiciosExtra as $servicios){
                $listaServicios[] = $servicios;
            }
        }

    

        $saveName = str_replace(' ', '_', $reserva->cliente->nombre_cliente);

        $dataConsumo = [
            'nombre'        => $reserva->cliente->nombre_cliente,
            'fecha_visita'  => $reserva->fecha_visita,
            'total'         => $total,
            'listaConsumos' => $listaConsumos,
            'listaServicios' => $listaServicios,
            'venta' => $reserva->venta
        ];

        $pdf = pdf::loadView('pdf.boleta.viewPDF', $dataConsumo)->setPaper([0,0,226.77,9999], 'portrait');
        // dd($pdf);
        return $pdf->stream("Boleta" . "_" . $saveName . "_" . $reserva->fecha_visita . ".pdf");
    }

    public function databoletaventadirecta(Request $request ,VentaDirecta $ventaDirecta)
    {

        $ventaDirecta->load('tipoTransaccion', 'user', 'detalles', 'propina');

        $total   = 0;
        $listaProductos = [];
        $idConsumo       = null;
        $cantidadPropina = null;


        if (isset($ventaDirecta)) {
            foreach ($ventaDirecta->detalles as $detalles) {
                $listaProductos[] = $detalles;
            }

        }

    

        $saveName = str_replace(' ', '_', $ventaDirecta->fecha);

        $dataConsumo = [
            'nombre'        => $ventaDirecta->user->name,
            'fecha_visita'  => $ventaDirecta->fecha,
            'total'         => $total,
            'listaConsumos' => $listaProductos,
            'venta' => $ventaDirecta,
        ];

        $pdf = pdf::loadView('pdf.boleta.ventaDirectaPDF', $dataConsumo)->setPaper([0,0,226.77,9999], 'portrait');
        // dd($pdf);
        return $pdf->stream("Boleta" . "_" . $saveName . "_" . $ventaDirecta->fecha_visita . ".pdf");
    }


    public function databoletaventaporoporo(Request $request ,PoroPoroVenta $poroVenta)
    {
        $poroVenta->load('tipoTransaccion', 'user', 'detalles');
        
        $total   = 0;
        $listaProductos = [];


        if (isset($poroVenta)) {
            foreach ($poroVenta->detalles as $detalles) {
                $listaProductos[] = $detalles;
            }

        }

    

        $saveName = str_replace(' ', '_', $poroVenta->fecha);

        $dataConsumo = [
            'nombre'        => $poroVenta->user->name,
            'fecha_visita'  => $poroVenta->fecha,
            'total'         => $total,
            'listaConsumos' => $listaProductos,
            'venta' => $poroVenta,
        ];

        $pdf = pdf::loadView('pdf.boleta.poroporoPDF', $dataConsumo)->setPaper([0,0,226.77,9999], 'portrait');
        // dd($pdf);
        return $pdf->stream("Boleta" . "_" . $saveName . "_" . $poroVenta->fecha_visita . ".pdf");
    }


}
