<?php

namespace App\Http\Controllers;

use App\DetalleVentaDirecta;
use App\Events\Consumos\NuevoConsumoAgregado;
use App\Producto;
use App\TipoProducto;
use App\TipoTransaccion;
use App\VentaDirecta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class VentaDirectaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $hoy = Carbon::today();
        $ventasDirectas = VentaDirecta::where('fecha', $hoy)
        ->with(['tipoTransaccion', 'user', 'propina'])
            ->get();

        return view('themes.backoffice.pages.venta_directa.index', compact('ventasDirectas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tiposTransacciones = TipoTransaccion::all();
        $productos = Producto::all();
        $tipos = TipoProducto::all();
        $listado = ['Aguas','Bebidas', 'Bebidas Calientes','Cervezas','Cócteles','Jugos Naturales','Spritz','Mocktails','Vinos','Sandwich y Pasteleria'];


        return view('themes.backoffice.pages.venta_directa.create', compact('tiposTransacciones', 'productos', 'tipos', 'listado'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $request->merge([
            'propina' => (float) str_replace(['$', '.'], '', str_replace(',', '.', $request->propina)),
            'subtotal'      => (int) str_replace(['$', '.', ','], '', $request->subtotal),
            'total'         => (int) str_replace(['$', '.', ','], '', $request->total),
        ]);

        $messages = [
            'productos.required' => 'Debe agregar al menos un producto.',
            'propina.numeric' => 'La propina debe ser un número.',
            'propina.min' => 'La propina no puede ser negativa.',
            'subtotal.required' => 'El subtotal es obligatorio.',
            'subtotal.numeric' => 'El subtotal debe ser un número.',
            'subtotal.min' => 'El subtotal no puede ser negativo.',
            'total.required' => 'El total es obligatorio.',
            'total.numeric' => 'El total debe ser un número.',
            'total.min' => 'El total no puede ser negativo.',
        ];
        
        $request->validate([
            'productos' => 'required|array',
            'propina' => 'nullable|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
        ], $messages);
        
     
        $productosAñadidos = array_filter($request->productos, function ($producto) {
            return isset($producto['cantidad']) && $producto['cantidad'] > 0;
        });

        $productos=[];
        $cliente=null;
        $ubicacion=null;
        $detallesVentas = [];
        $poseePropina = (int) $request->input('tiene_propina', 0);



        foreach ($productosAñadidos as $id => $producto) {
            $productos[]= $id;
        }

        $nombres=null;
        $nombres = Producto::whereIn('id', $productos)->pluck('nombre')->implode(', ');

        // dd([
        //     'tiene_propina' => $request->input('tiene_propina', 0),
        //     'valor_propina' => $request->propina,
        //     'subtotal' => $request->subtotal,
        //     'total' => $request->total,
        // ]);
        // Iniciar una transacción en la base de datos
        DB::transaction(function () use ($request, &$venta, &$productos, &$cliente, &$ubicacion, &$detallesVentas, $poseePropina, $nombres) {

            $fecha = Carbon::now()->toDateString();
            // Crear la venta directa
                $venta_directa = VentaDirecta::create([
                    'fecha' => $fecha,
                    'tiene_propina' => $poseePropina,
                    'valor_propina' => $request->propina,
                    'subtotal' => $request->subtotal,
                    'total' => $request->total,
                    'id_tipo_transaccion' => $request->id_tipo_transaccion,
                    'id_user' => auth()->user()->id,
                ]);

            if ($poseePropina && $request->propina > 0) {
                $venta_directa->propina()->create([
                    'fecha' => $fecha,
                    'cantidad' => $request->propina,
                ]);
            }

            // Crear los detalles de venta
            
            // Filtrar los productos del request con cantidad válida (mayor que 0)
            $productosValidos = array_filter($request->productos, function ($producto) {
                return isset($producto['cantidad']) && $producto['cantidad'] > 0;
            });

            // Recorrer los productos válidos y crear los detalles de la venta directa
            foreach ($productosValidos as $producto_id => $producto) {
                $detalle_venta_directa = DetalleVentaDirecta::create([
                    'venta_directa_id' => $venta_directa->id,
                    'producto_id' => $producto_id,
                    'cantidad' => $producto['cantidad'],
                    'precio_unitario' => $producto['valor'],
                    'subtotal' => $producto['valor'] * $producto['cantidad'], // Calcula el subtotal
                ]);

                $detallesVentas[] = $detalle_venta_directa;

            }




            $productosEvento = array_map(function ($detalle_venta_directa) use ($request) {
                $producto = Producto::find($detalle_venta_directa->producto_id);
                return [
                    'id' => $detalle_venta_directa->id,
                    'nombre' => $producto->nombre,
                    'cantidad' => $detalle_venta_directa->cantidad,
                    'cliente' => 'Venta Directa',
                    'ubicacion' => 'Venta Directa',
                ];
            }, $detallesVentas);
    
    
            broadcast(new NuevoConsumoAgregado([
                'mensaje'=>'Nuevo consumo agregado '.$nombres,
                'productos' => $productosEvento,
                'estado' => 'por-procesar'
            ]));
        });

        
        // Redirigir con éxito
        Alert::success('Éxito', 'Venta directa ingresada correctamente', 'Confirmar')->showConfirmButton();
        return redirect()->route('backoffice.venta_directa.index');
    }
    

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $ventaDirecta = VentaDirecta::with('detalles')->findOrFail($id);
        // dd($ventaDirecta);
        $tiposTransacciones = TipoTransaccion::all();
        $productos = Producto::all();
        $tipos = TipoProducto::all();
        $listado = ['Aguas','Bebidas', 'Bebidas Calientes','Cervezas','Cócteles','Jugos Naturales','Spritz','Mocktails','Vinos','Sandwich y Pasteleria'];

        return view('themes.backoffice.pages.venta_directa.edit', compact('ventaDirecta','tiposTransacciones', 'productos', 'tipos', 'listado'));
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
        $ventaDirecta = VentaDirecta::with('detalles', 'propina')->findOrFail($id);
        // dd($request->all(), $ventaDirecta);

        $request->merge([
            'propina' => (float) str_replace(['$', '.'], '', str_replace(',', '.', $request->propina)),
            'subtotal'      => (int) str_replace(['$', '.', ','], '', $request->subtotal),
            'total'         => (int) str_replace(['$', '.', ','], '', $request->total),
        ]);

        $messages = [
            'productos.required' => 'Debe agregar al menos un producto.',
            'propina.numeric' => 'La propina debe ser un número.',
            'propina.min' => 'La propina no puede ser negativa.',
            'subtotal.required' => 'El subtotal es obligatorio.',
            'subtotal.numeric' => 'El subtotal debe ser un número.',
            'subtotal.min' => 'El subtotal no puede ser negativo.',
            'total.required' => 'El total es obligatorio.',
            'total.numeric' => 'El total debe ser un número.',
            'total.min' => 'El total no puede ser negativo.',
        ];
        
        $request->validate([
            'productos' => 'required|array',
            'propina' => 'nullable|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
        ], $messages);

        $productosAñadidos = array_filter($request->productos, function ($producto) {
            return isset($producto['cantidad']) && $producto['cantidad'] > 0;
        });

        $productos=[];
        $detallesVentas = [];
        $poseePropina = (int) $request->input('tiene_propina', 0);



        foreach ($productosAñadidos as $id => $producto) {
            $productos[]= $id;
        }

        $nombres = null;
        $nombres = Producto::whereIn('id', $productos)->pluck('nombre')->implode(', ');


        DB::transaction(function () use ($request, $ventaDirecta, &$productos, &$detallesVentas, $poseePropina, $nombres) {

            $fecha = Carbon::now()->toDateString();
            // Crear la venta directa
                $ventaDirecta->update([
                    'tiene_propina' => $poseePropina,
                    'valor_propina' => $request->propina,
                    'subtotal' => $request->subtotal,
                    'total' => $request->total,
                    'id_tipo_transaccion' => $request->id_tipo_transaccion,
                    'id_user' => auth()->user()->id,
                ]);

                if ($poseePropina && $request->propina > 0) {
                    if ($ventaDirecta->propina) {
                        // Solo actualiza la cantidad, NO la fecha
                        $ventaDirecta->propina->update([
                            'cantidad' => $request->propina,
                        ]);
                    } else {
                        // Si no existe, se crea con fecha actual
                        $ventaDirecta->propina()->create([
                            'fecha' => now()->toDateString(),
                            'cantidad' => $request->propina,
                        ]);
                    }
                } else {
                    if ($ventaDirecta->propina) {
                        $ventaDirecta->propina->delete();
                    }
                }

            // Crear los detalles de venta
            
            // Filtrar los productos del request con cantidad válida (mayor que 0)
            $productosValidos = array_filter($request->productos, function ($producto) {
                return isset($producto['cantidad']) && $producto['cantidad'] > 0;
            });

            $ventaDirecta->detalles()->delete();

            // Recorrer los productos válidos y crear los detalles de la venta directa
            foreach ($productosValidos as $producto_id => $producto) {
                $detalle_venta_directa = DetalleVentaDirecta::create([
                    'venta_directa_id' => $ventaDirecta->id,
                    'producto_id' => $producto_id,
                    'cantidad' => $producto['cantidad'],
                    'precio_unitario' => $producto['valor'],
                    'subtotal' => $producto['valor'] * $producto['cantidad'],
                ]);

                $detallesVentas[] = $detalle_venta_directa;
            }




            $productosEvento = array_map(function ($detalle_venta_directa) use ($request) {
                $producto = Producto::find($detalle_venta_directa->producto_id);
                return [
                    'id' => $detalle_venta_directa->id,
                    'nombre' => $producto->nombre,
                    'cantidad' => $detalle_venta_directa->cantidad,
                    'cliente' => 'Venta Directa',
                    'ubicacion' => 'Venta Directa',
                ];
            }, $detallesVentas);
    
    
            broadcast(new NuevoConsumoAgregado([
                'mensaje'=>'Consumo actualizado '.$nombres,
                'productos' => $productosEvento,
                'estado' => 'por-procesar'
            ]));
        });


        // Redirigir con éxito
        Alert::success('Éxito', 'Venta directa modificada correctamente', 'Confirmar')->showConfirmButton();
        return redirect()->route('backoffice.venta_directa.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ventaDirecta = VentaDirecta::findOrFail($id);

        $ventaDirecta->delete();
        
        // Redirigir con éxito
        Alert::success('Éxito', 'Venta directa eliminada correctamente', 'Confirmar')->showConfirmButton();
        return redirect()->route('backoffice.venta_directa.index');
    }
}
