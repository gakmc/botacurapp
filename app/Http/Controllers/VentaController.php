<?php

namespace App\Http\Controllers;

use App\Asignacion;
use App\DetalleConsumo;
use App\Mail\VentaCerradaMailable;
use App\Propina;
use App\Reserva;
use App\TipoTransaccion;
use App\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use PDF;

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
            'venta' => $venta,
        ]);
    }

    public function cerrar(Venta $venta, Reserva $reserva)
    {
        $reserva->load('cliente', 'venta.tipoTransaccionAbono');
        $tipos = TipoTransaccion::all();
        $venta->load('consumos');

        // dd($reserva);

        return view('themes.backoffice.pages.venta.cerrar', [
            'reserva' => $reserva,
            'tipos' => $tipos,
            'venta' => $venta,
        ]);
    }

    public function cerrarventa(Request $request, Venta $venta, Reserva $reserva)
    {
        $venta = $reserva->venta;
        $consumo = $venta->consumos->first();
        $cliente = $reserva->cliente->nombre_cliente;

        
        DB::transaction(function () use ($request, &$venta, $reserva, $consumo) {

            // Verifica si el campo está en el request y luego asignar campo
            if ($request->has('diferencia_programa')) {
                $venta->diferencia_programa = $request->input('diferencia_programa');
            }

            // Generar url para almacenar imagen
            $url_diferencia = null;
            $filename = null;
            if ($request->hasFile('imagen_diferencia')) {

                $diferencia = $request->file('imagen_diferencia');
                $filename = time() . '-' . $diferencia->getClientOriginalName();
                $url_diferencia = 'temp/' . $filename; // Almacenamiento temporal
                Storage::disk('imagen_diferencia')->put($url_diferencia, File::get($diferencia));

            }

            // Si la imagen fue almacenada temporalmente, moverla a su ubicación final
            if ($filename) {
                $finalPath = '/' . $filename;
                Storage::disk('imagen_diferencia')->move('temp/' . $filename, $finalPath);
                $venta->imagen_diferencia = $finalPath;
            }

            // $venta->imagen_diferencia = $request->input('imagen_diferencia');

            if ($request->has('id_tipo_transaccion_diferencia')) {
                $venta->id_tipo_transaccion_diferencia = $request->input('id_tipo_transaccion_diferencia');
            }

            if ($request->has('descuento')) {
                $venta->descuento = $request->input('descuento');
            }

            if ($request->has('total_pagar')) {
                $venta->total_pagar = $request->input('total_pagar');
            }

            // Guarda los cambios
            $venta->save();

            
            if (!is_null($consumo)) {
                
                $detalles = $consumo->detallesConsumos;
                $totalPropinas = 0;
                
                if (!$request->has('propina')) {
                    DetalleConsumo::where('id_consumo', $consumo->id)
                    ->update(['genera_propina' => 0]);
                }else{
                    $fecha = Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d');
                    foreach ($detalles as $detalle) {
                        $totalPropinas += $detalle->subtotal*0.1;
                    }

                    $propina = Propina::create([
                        'fecha'=>$fecha,
                        'cantidad'=>$totalPropinas,
                        'id_consumo'=>$consumo->id
                    ]);

                    $asignacion = Asignacion::where('fecha', $fecha)->first();

                    if ($asignacion) {
                        $usuarios = $asignacion->users;
                        foreach ($usuarios as $user) {
                            DB::table('propina_user')->insert([
                                'id_user' => $user->id,
                                'id_propina' => $propina->id,
                                'monto_asignado' => $totalPropinas/count($usuarios),
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                    }

                }
            }

        });

        $total = 0;
        $propina = 'No Aplica';
        $visita = $reserva->visitas->first();
        $visita->load(['menus']);
        $menus = $visita->menus;
        $consumos = $venta->consumos;

        if (empty($consumos)) {
            $propina = 'No Aplica';
        } else {
            foreach ($consumos as $consumo) {
                foreach ($consumo->detallesConsumos as $detalles) {

                    if ($detalles->genera_propina) {
                        $total = $consumo->total_consumo;
                        $propina = 'Si';
                    } else {
                        $total = $consumo->subtotal;
                        $propina = 'No';
                    }
                }
            }
        }

        $data = [
            'nombre' => $reserva->cliente->nombre_cliente,
            'numero' => $reserva->cliente->whatsapp_cliente,
            'observacion' => $reserva->observacion ? $reserva->observacion : 'Sin Observaciones',
            'fecha_visita' => $reserva->fecha_visita,
            'programa' => $reserva->programa->nombre_programa,
            'personas' => $reserva->cantidad_personas,
            'menus' => $menus,
            'consumos' => $consumos,
            'venta' => $reserva->venta,
            'total' => $total,
            'propina' => $propina,
        ];

        // Generar el PDF
        $pdf = PDF::loadView('pdf.venta.viewPDF', $data);
        $pdfPath = storage_path('app/public/') . 'Detalle_Venta_' . str_replace(' ', '_', $reserva->cliente->nombre_cliente) . '_' . $reserva->fecha_visita . '.pdf';
        $pdf->save($pdfPath);
        $data['pdfPath']=$pdfPath;

        // Enviar el correo con el PDF adjunto
        Mail::to($reserva->cliente->correo)->send(new VentaCerradaMailable($data, $pdfPath));

        Alert::success('Éxito', 'Venta para ' . $cliente . ' cerrada con éxito', 'Confirmar')->showConfirmButton();
        return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva->id]);
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
