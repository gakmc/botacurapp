<?php

namespace App\Http\Controllers;

use App\Cotizacion;
use App\CotizacionItem;
use App\Mail\CotizacionMailable;
use App\Producto;
use App\Programa;
use App\Servicio;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CotizacionController extends Controller
{

    public function index()
    {
        $cotizaciones = Cotizacion::with('items')->get();
        // dd($cotizaciones);

        return view('themes.backoffice.pages.cotizacion.index', compact('cotizaciones'));
    }

    public function create()
    {
        $tiposProductos = ['Aguas','Bebidas', 'Bebidas Calientes','Cervezas','Cócteles','Jugos Naturales','Spritz','Mocktails','Vinos','Sandwich y Pasteleria'];

        $programas = Programa::all();
        $productos = Producto::activos()->whereHas('tipoProducto', function($query) use ($tiposProductos){
            $query->whereIn('nombre', $tiposProductos);
        })->get();
        $servicios = Servicio::all();

        return view('themes.backoffice.pages.cotizacion.create', compact('programas', 'productos','servicios'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'cliente' => 'required|string|max:255',
            'solicitante' => 'required|string|max:255',
            'correo' => 'nullable|email',
            'validez_dias' => 'required|integer|min:1',
            'fecha_reserva' => 'required|date',
            'programas' => 'nullable|array',
            'servicios' => 'nullable|array',
            'productos' => 'nullable|array',
        ],[
            'cliente.required' => 'El campo cliente es obligatorio.',
            'cliente.string' => 'El campo cliente debe ser un texto.',
            'cliente.max' => 'El campo cliente no debe exceder los 255 caracteres.',

            'solicitante.required' => 'El campo solicitante es obligatorio.',
            'solicitante.string' => 'El campo solicitante debe ser un texto.',
            'solicitante.max' => 'El campo solicitante no debe exceder los 255 caracteres.',

            'correo.email' => 'El correo ingresado no tiene un formato válido.',

            'validez_dias.required' => 'Debe ingresar la validez en días.',
            'validez_dias.integer' => 'El valor de validez debe ser un número entero.',
            'validez_dias.min' => 'La validez debe ser al menos de 1 día.',

            'fecha_reserva.required' => 'Debe ingresar una fecha de reserva.',
            'fecha_reserva.date' => 'La fecha de reserva no es válida.',

            'programas.array' => 'El formato de los programas no es válido.',
            'servicios.array' => 'El formato de los servicios no es válido.',
            'productos.array' => 'El formato de los productos no es válido.',
        ]);

        $fecha = Carbon::now();

        $cotizacion = DB::transaction(function () use ($request, $fecha) {
            $cotizacion = Cotizacion::create([
                'cliente' => $request->cliente,
                'solicitante' => $request->solicitante,
                'fecha_emision' => $fecha,
                'fecha_reserva' => Carbon::parse($request->fecha_reserva)->format('Y-m-d'),
                'validez_dias' => $request->validez_dias,
                'correo' => $request->correo,
            ]);


            $tipos = [
                'programas' => Programa::class,
                'servicios' => Servicio::class,
                'productos' => Producto::class,
            ];

            foreach ($tipos as $tipo => $modelo) {
                if ($request->has($tipo)) {
                    foreach ($request->$tipo as $key => $datos) {
                        $item = $modelo::findOrFail($key);
                        // $valor = $modelo === Producto::class ? $item->valor : ($modelo === Programa::class ? $item->valor_programa : $item->valor_servicio);

                        CotizacionItem::create([
                            'cotizacion_id' => $cotizacion->id,
                            'itemable_id' => $key,
                            'itemable_type' => $modelo,
                            'cantidad' => (int)$datos['cantidad'],
                            'valor_neto' => (int)$datos['subtotal'] / (int)$datos['cantidad'],
                            'total' =>  (int)$datos['subtotal'],
                        ]);
                    }
                }
            }

            return $cotizacion;

        });

        return redirect()->route('backoffice.cotizacion.show',$cotizacion->id)->with('success', 'Cotización creada correctamente.');

    }

    public function show($id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        $cotizacion->with(['items','items.programas','items.productos']);

        return view('themes.backoffice.pages.cotizacion.show', compact('cotizacion'));
    }

    public function edit($id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        $cotizacion->with(['items','items.programas','items.productos']);
        // dd($cotizacion->items);
        $tiposProductos = ['Aguas','Bebidas', 'Bebidas Calientes','Cervezas','Cócteles','Jugos Naturales','Spritz','Mocktails','Vinos','Sandwich y Pasteleria'];

        $programas = Programa::all();
        $productos = Producto::activos()->whereHas('tipoProducto', function($query) use ($tiposProductos){
            $query->whereIn('nombre', $tiposProductos);
        })->get();
        $servicios = Servicio::all();

        return view('themes.backoffice.pages.cotizacion.edit', compact('cotizacion', 'programas', 'productos','servicios'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        $cotizacion = Cotizacion::findOrFail($id);

        $request->validate([
            'cliente' => 'required|string|max:255',
            'solicitante' => 'required|string|max:255',
            'correo' => 'nullable|email',
            'validez_dias' => 'required|integer|min:1',
            'fecha_reserva' => 'required|date',
            'programas' => 'nullable|array',
            'servicios' => 'nullable|array',
            'productos' => 'nullable|array',
        ],[
            'cliente.required' => 'El campo cliente es obligatorio.',
            'cliente.string' => 'El campo cliente debe ser un texto.',
            'cliente.max' => 'El campo cliente no debe exceder los 255 caracteres.',

            'solicitante.required' => 'El campo solicitante es obligatorio.',
            'solicitante.string' => 'El campo solicitante debe ser un texto.',
            'solicitante.max' => 'El campo solicitante no debe exceder los 255 caracteres.',

            'correo.email' => 'El correo ingresado no tiene un formato válido.',

            'validez_dias.required' => 'Debe ingresar la validez en días.',
            'validez_dias.integer' => 'El valor de validez debe ser un número entero.',
            'validez_dias.min' => 'La validez debe ser al menos de 1 día.',

            'fecha_reserva.required' => 'Debe ingresar una fecha de reserva.',
            'fecha_reserva.date' => 'La fecha de reserva no es válida.',

            'programas.array' => 'El formato de los programas no es válido.',
            'servicios.array' => 'El formato de los servicios no es válido.',
            'productos.array' => 'El formato de los productos no es válido.',
        ]);

        $fecha = Carbon::now();

        DB::transaction(function () use ($request, $fecha, &$cotizacion) {
            $cotizacion->update([
                'cliente' => $request->cliente,
                'solicitante' => $request->solicitante,
                'fecha_emision' => $fecha,
                'fecha_reserva' => Carbon::parse($request->fecha_reserva)->format('Y-m-d'),
                'validez_dias' => $request->validez_dias,
                'correo' => $request->correo,
            ]);

            $cotizacion->items()->delete();

            $tipos = [
                'programas' => Programa::class,
                'servicios' => Servicio::class,
                'productos' => Producto::class,
            ];

            foreach ($tipos as $tipo => $modelo) {
                if ($request->has($tipo)) {
                    foreach ($request->$tipo as $key => $datos) {
                        $item = $modelo::findOrFail($key);
                        // $valor = $modelo === Producto::class ? $item->valor : ($modelo === Programa::class ? $item->valor_programa : $item->valor_servicio);

                        $cotizacion->items()->create([
                            'itemable_id' => $key,
                            'itemable_type' => $modelo,
                            'cantidad' => (int)$datos['cantidad'],
                            'valor_neto' => (int)$datos['subtotal']/(int)$datos['cantidad'],
                            'total' => (int)$datos['subtotal'],
                        ]);
                    }
                }
            }

        });

        return redirect()->route('backoffice.cotizacion.show',$cotizacion->id)->with('success', 'Cotización modificada correctamente.');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

            try {
                $cotizacion = Cotizacion::findOrFail($id);
                $cotizacionID = $cotizacion->id;

                $cotizacion->delete(); // Aquí se cae

                return redirect()->route('backoffice.cotizacion.index')->with('success', 'Cotización N.°'.$cotizacionID.' eliminada.');
            } catch (\Exception $e) {
                return back()->with('info', 'Error al eliminar: ' . $e->getMessage());
            }
    }

    public function visualizarPDF(Cotizacion $cotizacion)
    {
        $emitida = $cotizacion->fecha_emision->isoFormat('D [de] MMMM');
        $reserva = $cotizacion->fecha_reserva->isoFormat('D [de] MMMM');
        // La misma Blade que ya ves en pantalla puede servir para el PDF
        $pdf = Pdf::loadView('pdf.cotizacion.viewPDF', compact('cotizacion','emitida','reserva'));

        // stream() para ver en navegador – download() si prefieres forzar descarga
        return $pdf->stream('cotizacion_'.$cotizacion->id.'.pdf');
    }

    // public function enviarPDF(Cotizacion $cotizacion)
    // {

    //     $emitida = $cotizacion->fecha_emision->isoFormat('D [de] MMMM');
    //     $reserva = $cotizacion->fecha_reserva->isoFormat('D [de] MMMM');
    //     $pdfData = Pdf::loadView('pdf.cotizacion.viewPDF', compact('cotizacion','emitida','reserva'))
    //                     ->output();

    //     Mail::to($cotizacion->correo)
    //         ->queue(new CotizacionMailable($cotizacion,$pdfData));

    //     return back()->with('success','La cotizacion ha sido enviada satisfactoriamente.');
    // }

    public function enviarPDF(Cotizacion $cotizacion)
    {
        // Por si acaso, carga relaciones que uses en el Mailable (items, itemable, etc.)
        $cotizacion->load('items.itemable');

        Mail::to($cotizacion->correo)
            ->queue(new CotizacionMailable($cotizacion));
            
        return back()->with('success','La cotizacion ha sido enviada satisfactoriamente.');
    }
}
