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
use PDF;
use RealRashid\SweetAlert\Facades\Alert;

class ReservaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Carbon::setLocale('es');
        $alternativeView = $request->query('alternative', false);
        $fechaActual = Carbon::now()->startOfDay();

        if (!$alternativeView) {
            $reservas = Reserva::where('fecha_visita', '>=', $fechaActual)
                ->join('clientes as c', 'reservas.cliente_id', '=', 'c.id')
                ->join('visitas as v', 'v.id_reserva', '=', 'reservas.id')
                ->select('reservas.*', 'v.horario_sauna', 'v.horario_tinaja', 'v.horario_masaje', 'c.nombre_cliente')
                ->orderBy('v.horario_sauna', 'asc')
                ->get();
        } else {

            $reservas = Reserva::where('fecha_visita', '>=', $fechaActual)
                ->join('clientes as c', 'reservas.cliente_id', '=', 'c.id')
                ->join('visitas as v', 'v.id_reserva', '=', 'reservas.id')
                ->join('ubicaciones as u', 'v.id_ubicacion', '=', 'u.id')
                ->select('reservas.*', 'v.horario_sauna', 'v.horario_tinaja', 'v.horario_masaje', 'c.nombre_cliente', 'u.nombre')
                ->orderBy('reservas.fecha_visita', 'asc')
                ->orderBy('u.nombre', 'asc')
                ->get();
        }

        $reservasPorDia = $reservas->groupBy(function ($reserva) {
            return Carbon::parse($reserva->fecha_visita)->format('d-m-Y');
        });

        $perPage = 1;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $reservasPorDia->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $reservasPaginadas = new LengthAwarePaginator($currentItems, $reservasPorDia->count(), $perPage, $currentPage);
        $reservasPaginadas->setPath(request()->url());

        return view('themes.backoffice.pages.reserva.index', compact('reservasPaginadas', 'alternativeView'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($cliente)
    {
        $this->authorize('create', User::class);
        $cliente = Cliente::findOrFail($cliente);
        $programas = Programa::with('servicios')->get();
        $tipos = TipoTransaccion::all();

        return view('themes.backoffice.pages.reserva.create', [
            'cliente' => $cliente,
            'programas' => $programas,
            'tipos' => $tipos,
        ]);
    }

    public function verificarUbicaciones(Request $request)
    {
        $fechaSeleccionada = $request->input('fecha');

        $ubicacionesOcupadas = DB::table('visitas')
            ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
            ->join('ubicaciones', 'visitas.id_ubicacion', '=', 'ubicaciones.id')
            ->where('reservas.fecha_visita', $fechaSeleccionada)
            ->pluck('ubicaciones.nombre')
            ->map(function ($nombre) {
                return $nombre;
            })
            ->toArray();

        $ubicacionesAll = DB::table('ubicaciones')
            ->select('id', 'nombre')
            ->get();

        $ubicaciones = $ubicacionesAll->filter(function ($ubicacion) use ($ubicacionesOcupadas) {
            return !in_array($ubicacion->nombre, $ubicacionesOcupadas);
        })->values();


        return response()->json($ubicaciones);
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
        $almuerzosExtra = null;

        // Verificar si el programa seleccionado incluye un masaje
        $programa = Programa::find($request->id_programa);

        // Buscar si el programa tiene un servicio de masaje
        $incluyeMasaje = $programa->servicios()->whereIn('nombre_servicio', ['Masaje', 'Masajes', 'masaje', 'masajes'])->exists();

        try {

            // Iniciar la transacción
            DB::transaction(function () use ($request, &$reserva, $incluyeMasaje, &$masajesExtra, &$almuerzosExtra) {

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

                $consumo = null;

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
                        $consumo->total_consumo += $subtotal;
                        $consumo->save();
                    }

                }

                if ($request->filled('agregar_almuerzos')) {

                    $almuerzosExtra = $request->filled('agregar_almuerzos');

                    if (!$consumo) {
                        $consumo = Consumo::create([
                            'id_venta' => $venta->id,
                            'subtotal' => 0,
                            'total_consumo' => 0,
                        ]);
                    }

                    $servicioAlmuerzo = Servicio::whereIn('nombre_servicio', ['Almuerzo', 'Almuerzos', 'almuerzo', 'almuerzos'])->first();

                    if ($servicioAlmuerzo) {
                        $subtotal = 0;
                        $cantidadAlmuerzosExtra = intval($request->input('cantidad_personas'));
                        $subtotalAlmuerzos = $servicioAlmuerzo->valor_servicio * $cantidadAlmuerzosExtra;
                        $subtotal = $subtotalAlmuerzos;

                        // Crear el detalle del servicio extra
                        DetalleServiciosExtra::create([
                            'cantidad_servicio' => $cantidadAlmuerzosExtra,
                            'subtotal' => $subtotalAlmuerzos,
                            'id_consumo' => $consumo->id,
                            'id_servicio_extra' => $servicioAlmuerzo->id,
                        ]);

                        $consumo->subtotal += $subtotal;
                        $consumo->total_consumo += $subtotal;
                        $consumo->save();
                    }

                }
            });

            // Mostrar alerta de éxito
            Alert::success('Éxito', 'Reserva realizada con éxito', 'Confirmar')->showConfirmButton();

            session()->put([
                'masajesExtra' => $masajesExtra,
                'almuerzosExtra' => $almuerzosExtra,
            ]);

            // Redirigir fuera de la transacción
            return redirect()->route('backoffice.reserva.visitas.create', ['reserva' => $reserva->id]);

        } catch (\Error $e) {
            Alert::error('Falló', 'Error: ' . $e, 'Confirmar')->showConfirmButton();
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
        $reserva->load('venta.consumos.detallesConsumos.producto', 'venta.consumos.detalleServiciosExtra.servicio', 'visitas.menus', 'visitas.menus.productoEntrada', 'visitas.menus.productoFondo', 'visitas.menus.productoacompanamiento');

        $servicios = Servicio::all();

        return view('themes.backoffice.pages.reserva.show', [
            'reserva' => $reserva,
            'servicios' => $servicios,
        ]);
    }

    public function showAbonoImage($id)
    {
        $reserva = Reserva::findOrFail($id);

        // Verificar si el archivo de abono existe
        if (Storage::disk('imagen_abono')->exists($reserva->venta->imagen_abono)) {
            $file = Storage::disk('imagen_abono')->get($reserva->venta->imagen_abono);
            $mimeType = Storage::disk('imagen_abono')->mimeType($reserva->venta->imagen_abono);

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
            $mimeType = Storage::disk('imagen_diferencia')->mimeType($reserva->venta->imagen_diferencia);

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

    public function generarPDF(Reserva $reserva)
    {
        $reserva->load('venta.consumos.detallesConsumos.producto', 'venta.consumos.detalleServiciosExtra.servicio', 'visitas.menus', 'visitas.menus.productoEntrada', 'visitas.menus.productoFondo', 'visitas.menus.productoacompanamiento');

        $total = 0;
        $propina = 'No Aplica';
        $visita = $reserva->visitas->first();
        $visita->load(['menus']);
        $menus = $visita->menus;
        $consumos = $reserva->venta->consumos;

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

        $saveName = str_replace(' ', '_', $reserva->cliente->nombre_cliente);

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

        // dd($data);

        $pdf = PDF::loadView('pdf.venta.viewPDF', $data);
        // return $pdf->download('factura.pdf');
        return $pdf->stream('Detalle_Venta' . '_' . $saveName . '_' . $reserva->fecha_visita . '.pdf');

    }

    public function indexall()
    {
        Carbon::setLocale('es');

        // Vista anterior
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $reservasPorMes = Reserva::with(['cliente', 'visitas', 'programa.servicios'])
            ->orderBy('fecha_visita')
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->fecha_visita)->format('Y-m');
            });

        return view('themes.backoffice.pages.reserva.index_all', compact('reservasPorMes'));
    }
}
