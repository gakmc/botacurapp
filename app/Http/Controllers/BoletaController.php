<?php

namespace App\Http\Controllers;

use App\Reserva;
use App\Consumo;
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

        $reserva->load('venta.consumo','venta.consumo.detallesConsumos.producto', 'venta.consumo.detalleServiciosExtra.servicio');

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
}
