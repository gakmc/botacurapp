<?php

namespace App\Http\Controllers;

use App\DetalleConsumo;
use App\Events\Consumos\EstadoConsumoActualizado;
use App\Producto;
use App\Visita;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BarmanController extends Controller
{
    public function index()
    {
        $fechaActual = Carbon::now()->startOfDay();

        $productos = DB::table('reservas')
            ->join('ventas', 'reservas.id', '=', 'ventas.id_reserva')
            ->join('consumos', 'ventas.id', '=', 'consumos.id_venta')
            ->join('visitas', 'reservas.id', '=', 'visitas.id_reserva')
            ->join('ubicaciones', 'ubicaciones.id', '=', 'visitas.id_ubicacion')
            ->join('detalles_consumos', 'consumos.id', '=', 'detalles_consumos.id_consumo')
            ->join('productos', 'detalles_consumos.id_producto', '=', 'productos.id')
            ->join('tipos_productos', 'productos.id_tipo_producto', '=', 'tipos_productos.id')
            ->join('sectores', 'tipos_productos.id_sector', '=', 'sectores.id')
            ->join('clientes', 'reservas.cliente_id', '=', 'clientes.id')
            ->where('reservas.fecha_visita', '>=', $fechaActual)
        // ->whereIn('tipos_productos.nombre', ['bebestibles', 'cocteles'])
            ->where(function ($query) {
                $query->where('sectores.nombre', 'barra');
            })
            ->select(
                'clientes.nombre_cliente',
                'detalles_consumos.cantidad_producto',
                'detalles_consumos.estado as estado',
                'detalles_consumos.id as id',
                'detalles_consumos.subtotal',
                'detalles_consumos.created_at as creado',
                'productos.nombre as producto',
                'reservas.fecha_visita',
                'tipos_productos.nombre as categoria',
                'ubicaciones.nombre as ubicacion'
            )
            ->orderBy('reservas.fecha_visita', 'asc')
            ->get();

        // dd($productos);

        return view('themes.backoffice.pages.barman.index', [
            'productos' => $productos,
        ]);

    }

    public function actualizarEstado(Request $request, $id)
    {
        // // Buscar el detalle de consumo por ID
        // $detalleConsumo = DB::table('detalles_consumos')->where('id', $id)->first();

        // if (!$detalleConsumo) {
        //     return response()->json(['error' => 'Detalle de consumo no encontrado.'], 404);
        // }

        // // Actualizar el estado del detalle
        // DB::table('detalles_consumos')
        //     ->where('id', $id)
        //     ->update(['estado' => $request->input('estado')]);

        // return response()->json(['success' => true, 'estado' => $request->input('estado')]);

        $detalle = DetalleConsumo::findOrFail($id);
        $detalle->estado = $request->estado;
        $detalle->save();

        $visita = Visita::where('id_reserva', $detalle->consumo->venta->reserva->id)->first();

        $producto = [
            'nombre' => $detalle->producto->nombre,
            'cantidad' => $detalle->cantidad_producto,
            'cliente' => $detalle->consumo->venta->reserva->cliente->nombre_cliente,
            'ubicacion' => $visita->ubicacion->nombre,
        ];

        broadcast(new EstadoConsumoActualizado($detalle->id, $detalle->estado, $producto));

        return response()->json(['success' => true, 'estado' => $request->input('estado')]);
    }

    public function bebidas()
    {
        $fechaActual = Carbon::now()->startOfDay();

        $productos = DB::table('reservas')
            ->join('ventas', 'reservas.id', '=', 'ventas.id_reserva')
            ->join('consumos', 'ventas.id', '=', 'consumos.id_venta')
            ->join('visitas', 'reservas.id', '=', 'visitas.id_reserva')
            ->join('ubicaciones', 'ubicaciones.id', '=', 'visitas.id_ubicacion')
            ->join('detalles_consumos', 'consumos.id', '=', 'detalles_consumos.id_consumo')
            ->join('productos', 'detalles_consumos.id_producto', '=', 'productos.id')
            ->join('tipos_productos', 'productos.id_tipo_producto', '=', 'tipos_productos.id')
            ->join('sectores', 'tipos_productos.id_sector', '=', 'sectores.id')
            ->join('clientes', 'reservas.cliente_id', '=', 'clientes.id')
            ->where('reservas.fecha_visita', '>=', $fechaActual)
        // ->whereIn('tipos_productos.nombre', ['bebestibles', 'cocteles'])
            ->where(function ($query) {
                $query->where('sectores.nombre', 'barra');
            })
            ->select(
                'clientes.nombre_cliente',
                'detalles_consumos.cantidad_producto',
                'detalles_consumos.estado as estado',
                'detalles_consumos.id as id',
                'detalles_consumos.subtotal',
                'detalles_consumos.created_at as creado',
                'productos.nombre as producto',
                'reservas.fecha_visita',
                'tipos_productos.nombre as categoria',
                'ubicaciones.nombre as ubicacion'
            )
            ->orderBy('reservas.fecha_visita', 'asc')
            ->get();

        // dd($productos);

        return view('themes.backoffice.pages.barman.bebida', [
            'productos' => $productos,
        ]);
    }


}
