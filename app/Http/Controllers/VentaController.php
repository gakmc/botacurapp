<?php

namespace App\Http\Controllers;

use App\Venta;
use App\Reserva;
use App\TipoTransaccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

use RealRashid\SweetAlert\Facades\Alert;

class VentaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($reserva)
    {
        // $reserva = Reserva::findOrFail($reserva);
        // $tipos = TipoTransaccion::all();

        // return view('themes.backoffice.pages.venta.create', [
        //     'reserva' => $reserva,
        //     'tipos' => $tipos,
        // ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Reserva $reserva)
    {
        // dd($request);

        // $url_abono = null;
        // $url_diferencia = null;
        
        // if($request->hasfile('imagen_abono')){
        //     $abono = $request->file('imagen_abono');
        //     $filename = time().'-'.$abono->getClientOriginalName();
        //     Storage::disk('imagen_abono')->put($filename, File::get($abono));
        //     $url_abono = $filename;
        // }

        // if($request->hasfile('imagen_diferencia')){
        //     $diferencia = $request->file('imagen_diferencia');
        //     $filename = time().'-'.$diferencia->getClientOriginalName();
        //     Storage::disk('imagen_diferencia')->put($filename, File::get($diferencia));
        //     $url_diferencia = $filename;
        // }


        // $venta = Venta::create([
        //     'abono_programa' => $request->input('abono_programa'),
        //     'imagen_abono' => $url_abono,
        //     'diferencia_programa' => $request->input('diferencia_programa'),
        //     'imagen_diferencia' => $url_diferencia,
        //     'descuento' => $request->input('descuento'),
        //     'total_pagar' => $request->input('total_pagar'),
        //     'id_reserva' => $request->input('id_reserva'),
        //     'id_tipo_transaccion_abono' => $request->input('id_tipo_transaccion_abono'),
        //     'id_tipo_transaccion_diferencia' => $request->input('id_tipo_transaccion_diferencia'),
        // ]);

        // Alert::success('Éxito', 'Se ha generado la venta')->showConfirmButton('Confirmar');
        // return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Venta  $venta
     * @return \Illuminate\Http\Response
     */
    public function show(Venta $venta)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Venta  $venta
     * @return \Illuminate\Http\Response
     */
    public function edit(Venta $venta, Reserva $reserva)
    {
        $reserva->load('cliente', 'venta.tipoTransaccionAbono'); 
        $tipos = TipoTransaccion::all();

        // dd($reserva);

        return view('themes.backoffice.pages.venta.edit', [
            'reserva' => $reserva,
            'tipos' => $tipos,
            'venta' => $venta
        ]);
    }

    public function cerrar(Venta $venta, Reserva $reserva)
    {
        $reserva->load('cliente', 'venta.tipoTransaccionAbono'); 
        $tipos = TipoTransaccion::all();

        // dd($reserva);

        return view('themes.backoffice.pages.venta.cerrar', [
            'reserva' => $reserva,
            'tipos' => $tipos,
            'venta' => $venta
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Venta  $venta
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Venta $venta)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Venta  $venta
     * @return \Illuminate\Http\Response
     */
    public function destroy(Venta $venta)
    {
        //
    }
}
