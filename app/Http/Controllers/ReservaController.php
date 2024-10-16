<?php

namespace App\Http\Controllers;

use App\Cliente;
use App\Consumo;
use App\DetalleServiciosExtra;
use App\Http\Requests\Reserva\StoreRequest;
use App\Programa;
use App\Reserva;
use App\Servicio;
use App\TipoTransaccion;
use App\User;
use App\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class ReservaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Carbon::setLocale('es');

        // Vista anterior
        // $currentMonth = Carbon::now()->month;
        // $currentYear = Carbon::now()->year;

        // $reservasPorMes = Reserva::with(['cliente', 'visitas', 'programa.servicios'])
        //     ->orderBy('fecha_visita')
        //     ->get()
        //     ->groupBy(function ($date) {
        //         return Carbon::parse($date->fecha_visita)->format('Y-m');
        //     });

        // Vista actual
        $fechaActual = Carbon::now()->startOfDay();

        // $reservas = Reserva::where('fecha_visita', '>=', $fechaActual)
        //     ->with(['cliente', 'visitas', 'programa.servicios'])
        //     ->orderBy('fecha_visita')
        //     ->get();


        $reservas = Reserva::where('fecha_visita', '>=', $fechaActual)
        ->join('clientes as c', 'reservas.cliente_id', '=', 'c.id')
        ->join('visitas as v', 'v.id_reserva', '=', 'reservas.id')
        ->select('reservas.*', 'v.horario_sauna', 'v.horario_tinaja', 'v.horario_masaje', 'c.nombre_cliente')
        ->orderBy('v.horario_sauna', 'asc')
        ->get();


        // Agrupar reservas por fecha
        $reservasPorDia = $reservas->groupBy(function ($reserva) {
            return Carbon::parse($reserva->fecha_visita)->format('d-m-Y');
        });

        // Paginación manual de los días
        $perPage = 1; // Número de días por página
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $reservasPorDia->slice(($currentPage - 1) * $perPage, $perPage)->all();

        // Crear el paginador manualmente
        $reservasPaginadas = new LengthAwarePaginator($currentItems, $reservasPorDia->count(), $perPage, $currentPage);
        $reservasPaginadas->setPath(request()->url());

        return view('themes.backoffice.pages.reserva.index', compact('reservasPaginadas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($cliente)
    {
        $this->authorize('create',User::class);
        $cliente = Cliente::findOrFail($cliente);
        $programas = Programa::with('servicios')->get();
        $tipos = TipoTransaccion::all();

        return view('themes.backoffice.pages.reserva.create', [
            'cliente' => $cliente,
            'programas' => $programas,
            'tipos' => $tipos,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request, Reserva $reserva)
    {
        $masajesExtra = null;

        // Verificar si el programa seleccionado incluye un masaje
        $programa = Programa::find($request->id_programa);

        // Buscar si el programa tiene un servicio de masaje
        $incluyeMasaje = $programa->servicios()->whereIn('nombre_servicio', ['Masaje', 'Masajes', 'masaje', 'masajes'])->exists();

        try {


        // Iniciar la transacción
        DB::transaction(function () use ($request, &$reserva, $incluyeMasaje, &$masajesExtra) {

            // Asignar el id del cliente con la reserva
            if ($request->has('cliente_id')) {
                $cliente = $request->cliente_id;
                $request->merge(['cliente_id' => $cliente]);
            }

            // Asignar el user_id del usuario autenticado
            $user_id = auth()->id();
            $request->merge(['user_id' => $user_id]);

            // Crear la reserva
            $reserva = Reserva::create($request->all());

            // Asignar cantidad_personas a cantidad_masajes solo si el programa incluye masaje
            if ($incluyeMasaje) {
                $reserva->update(['cantidad_masajes' => $reserva->cantidad_personas]);
            } else {
                // Dejarlo nulo explícitamente si no hay masaje
                $reserva->update(['cantidad_masajes' => null]);
            }

            // Generar url para almacenar imagen
            $url_abono = null;
            $filename = null;

            if ($request->hasFile('imagen_abono')) {
                $abono = $request->file('imagen_abono');
                $filename = time() . '-' . $abono->getClientOriginalName();
                $url_abono = 'temp/' . $filename; // Almacenamiento temporal
                Storage::disk('imagen_abono')->put($url_abono, File::get($abono));
            }

            // if ($request->hasFile('imagen_abono')) {
            //     $abono = $request->file('imagen_abono');
            //     $filename = time() . '-' . $abono->getClientOriginalName();
            //     Storage::disk('imagen_abono')->put($filename, File::get($abono));
            //     $url_abono = $filename;
            // }

            // Crear la venta relacionada con la reserva
            $venta = Venta::create([
                'id_reserva' => $reserva->id,
                'abono_programa' => $request->abono_programa,
                'imagen_abono' => $url_abono,
                'id_tipo_transaccion_abono' => $request->tipo_transaccion,
                'total_pagar' => $request->total_pagar,
            ]);

            // Si la imagen fue almacenada temporalmente, moverla a su ubicación final
            if ($filename) {
                $finalPath = '/' . $filename;
                Storage::disk('imagen_abono')->move('temp/' . $filename, $finalPath);
                $venta->update(['imagen_abono' => $finalPath]);
            }


            
            if ($request->filled('cantidad_masajes_extra')) {

                $masajesExtra = $request->filled('cantidad_masajes_extra');

                $consumo = Consumo::create([
                    'id_venta' => $venta->id,
                    'subtotal' => 0,
                    'total_consumo' => 0,
                ]);
                
                $servicioMasaje = Servicio::whereIn('nombre_servicio', ['Masaje', 'Masajes', 'masaje', 'masajes'])->first();
                
                if ($servicioMasaje) {
                    $subtotal = 0;
                    $cantidadMasajesExtra = intval($request->input('cantidad_masajes_extra'));
                    $subtotalMasajes = $servicioMasaje->valor_servicio * $cantidadMasajesExtra;
                    $subtotal = $subtotalMasajes;

                    // Crear el detalle del servicio extra
                    DetalleServiciosExtra::create([
                        'cantidad_servicio' => $cantidadMasajesExtra,
                        'subtotal' => $subtotalMasajes,
                        'id_consumo' => $consumo->id,
                        'id_servicio_extra' => $servicioMasaje->id,
                    ]);

                    $consumo->subtotal += $subtotal;
                    $consumo->save();
                }


            }
        });

        // Mostrar alerta de éxito
        Alert::success('Éxito', 'Reserva realizada con éxito', 'Confirmar')->showConfirmButton();

        session()->put('masajesExtra', $masajesExtra);

        // Redirigir fuera de la transacción
        return redirect()->route('backoffice.reserva.visitas.create', ['reserva' => $reserva->id]);

    } catch (\Error $e) {
        Alert::error('Falló', 'Error: '.$e, 'Confirmar')->showConfirmButton();
        return redirect()->back()->withInput();
    }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Reserva  $reserva
     * @return \Illuminate\Http\Response
     */
    public function show(Reserva $reserva)
    {
        $reserva->load('venta.consumos.detallesConsumos.producto', 'visitas.menus','visitas.menus.productoEntrada','visitas.menus.productoFondo','visitas.menus.productoacompanamiento');

        return view('themes.backoffice.pages.reserva.show', [
            'reserva' => $reserva,
        ]);
    }

    public function showAbonoImage($id)
    {
        $reserva = Reserva::findOrFail($id);

        // Verificar si el archivo de abono existe
        if (Storage::disk('imagen_abono')->exists($reserva->venta->imagen_abono)) {
            $file = Storage::disk('imagen_abono')->get($reserva->venta->imagen_abono);
            $mimeType = Storage::disk('imagen_abono')->Storage::mimeType($reserva->venta->imagen_abono);

            return response($file, 200)->header('Content-Type', $mimeType);
        }

        return abort(404, 'Imagen de abono no encontrada');
    }

    public function showDiferenciaImage($id)
    {
        $reserva = Reserva::findOrFail($id);

        // Verificar si el archivo de diferencia existe
        if (Storage::disk('imagen_diferencia')->exists($reserva->venta->imagen_diferencia)) {
            $file = Storage::disk('imagen_diferencia')->get($reserva->venta->imagen_diferencia);
            $mimeType = Storage::disk('imagen_diferencia')->Storage::mimeType($reserva->venta->imagen_diferencia);

            return response($file, 200)->header('Content-Type', $mimeType);
        }

        // return abort(404, 'Imagen de diferencia no encontrada');
        return redirect('https://via.placeholder.com/200x300');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Reserva  $reserva
     * @return \Illuminate\Http\Response
     */
    public function edit(Reserva $reserva)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Reserva  $reserva
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Reserva $reserva)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Reserva  $reserva
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reserva $reserva)
    {
        //
    }
}
