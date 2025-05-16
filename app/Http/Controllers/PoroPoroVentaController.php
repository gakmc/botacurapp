<?php

namespace App\Http\Controllers;

use App\PoroDetalleVenta;
use App\PoroPoro;
use App\PoroPoroVenta;
use App\TipoTransaccion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class PoroPoroVentaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $poroProductos = PoroPoro::all();
        $poroVentas = PoroPoroVenta::all();

        return view('themes.backoffice.pages.poroporo.venta.index', compact('poroProductos', 'poroVentas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $productos = PoroPoro::all();
        $tiposTransacciones = TipoTransaccion::all();
        return view('themes.backoffice.pages.poroporo.venta.create', compact('productos', 'tiposTransacciones'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_tipo_transaccion' => 'required|exists:tipos_transacciones,id',
            'productos' => 'required|array|min:1',
            'productos.*.cantidad' => 'required|integer|min:1',
        ], [
            'id_tipo_transaccion.required' => 'Debe seleccionar un método de pago.',
            'id_tipo_transaccion.exists' => 'El método de pago seleccionado no es válido.',
            
            'productos.required' => 'Debe seleccionar al menos un producto para registrar la venta.',
            'productos.array' => 'El formato de los productos no es válido.',
            'productos.min' => 'Debe agregar al menos un producto a la venta.',
            
            'productos.*.cantidad.required' => 'Debe ingresar una cantidad para cada producto.',
            'productos.*.cantidad.integer' => 'La cantidad del producto debe ser un número entero.',
            'productos.*.cantidad.min' => 'La cantidad mínima para cada producto es 1.',
        ]);

        // dd($request->all());

        DB::transaction(function () use ($request){

            $venta = PoroPoroVenta::create([
                'fecha' => Carbon::now(),
                'total' => 0,
                'id_tipo_transaccion' => $request->id_tipo_transaccion,
                'id_user' => auth()->user()->id,
            ]);

            $totalVenta = 0;

            foreach ($request->productos as $id => $detalle) {
                $producto = PoroPoro::findOrFail($id);
                $cantidad = $detalle['cantidad'];
                $precioUnitario = $producto->valor;
                $subtotal = $precioUnitario * $cantidad;

                $detalleVenta = $venta->detalles()->create([
                    'poro_venta_id' => $venta->id,
                    'poro_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $subtotal
                ]);
                
                $totalVenta += $subtotal;
            }

            $venta->total = $totalVenta;
            $venta->save();



        });

        Alert::success('Éxito','Venta generada exitosamente')->showConfirmButton('Confirmar');
        return redirect()->route('backoffice.ventas_poroporo.index');
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
        $poroVenta = PoroPoroVenta::findOrFail($id);
        $poroVenta->load('tipoTransaccion', 'user', 'detalles');
        $productos = PoroPoro::all();
        $tiposTransacciones = TipoTransaccion::all();

        $productosIniciales = $poroVenta->detalles->map(function ($detalle) {
            return [
                'id' => $detalle->poro_id,
                'nombre' => $detalle->poro->nombre,
                'valor' => $detalle->precio_unitario,
                'cantidad' => $detalle->cantidad
            ];
        })->values()->all();
        
        return view('themes.backoffice.pages.poroporo.venta.edit', compact('poroVenta', 'productos', 'tiposTransacciones', 'productosIniciales'));
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
        dd($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
