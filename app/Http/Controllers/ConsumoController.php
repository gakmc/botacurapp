<?php

namespace App\Http\Controllers;

use App\Consumo;
use App\DetalleConsumo;
use App\DetalleServiciosExtra;
use App\Events\NuevoConsumoAgregado;
use App\Producto;
use App\Servicio;
use App\TipoProducto;
use App\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class ConsumoController extends Controller
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

    public function service_create($venta)
    {
        $venta = Venta::findOrFail($venta);
        $servicios = Servicio::all();
        return view('themes.backoffice.pages.consumo.create_service', [
            'venta' => $venta,
            'servicios' => $servicios,
        ]);
    }

    public function service_store(Request $request, Venta $venta)
    {

        DB::transaction(function () use ($request, &$venta) {
            // Verificar si ya existe un consumo para esta venta
            $consumo = Consumo::where('id_venta', $request->id_venta)->first();

            // Si no existe, creamos el consumo con valores iniciales
            if (!$consumo) {
                $consumo = Consumo::create([
                    'id_venta' => $request->id_venta,
                    'subtotal' => 0,
                    'total_consumo' => 0,
                ]);
            }

            // Inicializar variables
            $totalSubtotal = 0;
            $nuevoSubtotal = 0;

            // Filtrar los productos del request con cantidad válida (mayor que 0)
            $serviciosValidos = array_filter($request->servicios, function ($servicio) {
                return isset($servicio['cantidad']) && $servicio['cantidad'] > 0;
            });

            // Recorrer los productos válidos y crear los detalles de consumo
            foreach ($serviciosValidos as $servicio_id => $servicio) {
                DetalleServiciosExtra::create([
                    'id_consumo' => $consumo->id,
                    'id_servicio_extra' => $servicio_id,
                    'cantidad_servicio' => $servicio['cantidad'],
                    'subtotal' => $servicio['precio'] * $servicio['cantidad'],
                ]);

                // Sumar al subtotal del nuevo consumo
                $nuevoSubtotal += $servicio['cantidad'] * $servicio['precio'];

            }

            $consumo->subtotal += $nuevoSubtotal;
            $consumo->total_consumo += $nuevoSubtotal;

            $consumo->save();

        });

        $venta = Venta::where('id', $request->id_venta)->first();

        Alert::success('Éxito', 'Servicio extra ingresado correctamente', 'Confirmar')->showConfirmButton();
        return redirect()->route('backoffice.reserva.show', $venta->reserva->id);
    }

    public function create($venta)
    {
        $venta = Venta::findOrFail($venta);
        $tipos = TipoProducto::all();
        $listado = ['Bebestibles','Cócteles','Sandwich y Pastelería'];

        return view('themes.backoffice.pages.consumo.create', [
            'venta' => $venta,
            'tipos' => $tipos,
            'listado' => $listado,
        ]);
    }

    public function store(Request $request, Venta $venta)
    {
        $productosAñadidos = array_filter($request->productos, function ($producto) {
            return isset($producto['cantidad']) && $producto['cantidad'] > 0;
        });

        $productos=[];

        foreach ($productosAñadidos as $id => $producto) {
            $productos[]= $id;
        }

        $nombres=null;
        $nombres = Producto::whereIn('id', $productos)->pluck('nombre')->implode(', ');


        // Iniciar una transacción en la base de datos
        DB::transaction(function () use ($request, &$venta) {

            // Verificar si ya existe un consumo para esta venta
            $consumo = Consumo::where('id_venta', $request->id_venta)->first();

            // Si no existe, creamos el consumo con valores iniciales
            if (!$consumo) {
                $consumo = Consumo::create([
                    'id_venta' => $request->id_venta,
                    'subtotal' => 0,
                    'total_consumo' => 0,
                ]);
            }

            // Inicializar variables
            $totalSubtotal = 0;
            $nuevoSubtotal = 0;
            $generaPropina = true;

            // Filtrar los productos del request con cantidad válida (mayor que 0)
            $productosValidos = array_filter($request->productos, function ($producto) {
                return isset($producto['cantidad']) && $producto['cantidad'] > 0;
            });

            // Recorrer los productos válidos y crear los detalles de consumo
            foreach ($productosValidos as $producto_id => $producto) {
                DetalleConsumo::create([
                    'id_consumo' => $consumo->id,
                    'id_producto' => $producto_id,
                    'cantidad_producto' => $producto['cantidad'],
                    'subtotal' => $producto['valor'] * $producto['cantidad'], // Calcula el subtotal
                    'genera_propina' => 1,
                ]);

                // Sumar al subtotal del nuevo consumo
                $nuevoSubtotal += $producto['cantidad'] * $producto['valor'];

                // Verificar si alguno de los productos genera propina
                if (isset($producto['genera_propina']) && $producto['genera_propina']) {
                    $generaPropina = true;
                }
            }

            // Sumar el nuevo subtotal al subtotal actual del consumo
            $consumo->subtotal += $nuevoSubtotal;

            // Calcular la propina solo del nuevo subtotal
            $propina = $nuevoSubtotal * 0.1;

            // Recalcular el total del consumo (se añade un 10% en propina)
            $totalConPropina = $consumo->subtotal + $propina;

            // Actualizar el consumo con los nuevos totales
            $consumo->total_consumo = $totalConPropina;
            $consumo->save();
        });

        $venta = Venta::where('id', $request->id_venta)->first();

        broadcast(new NuevoConsumoAgregado('Nuevo consumo agregado'.$nombres));
        // Redirigir con éxito
        Alert::success('Éxito', 'Consumo ingresado correctamente', 'Confirmar')->showConfirmButton();
        return redirect()->route('backoffice.reserva.show', $venta->reserva->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Consumo  $consumo
     * @return \Illuminate\Http\Response
     */
    public function show(Consumo $consumo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Consumo  $consumo
     * @return \Illuminate\Http\Response
     */
    public function edit(Consumo $consumo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Consumo  $consumo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Consumo $consumo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Consumo  $consumo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Consumo $consumo)
    {
        //
    }
}
