<?php
namespace App\Http\Controllers;

use App\Asignacion;
use App\Cliente;
use App\Consumo;
use App\DetalleServiciosExtra;
use App\Http\Requests\Reserva\StoreRequest;
use App\Http\Requests\Reserva\UpdateRequest;
use App\LugarMasaje;
use App\Masaje;
use App\Menu;
use App\Producto;
use App\Programa;
use App\Propina;
use App\Reagendamiento;
use App\Reserva;
use App\Servicio;
use App\TipoTransaccion;
use App\User;
use App\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
// use PDF;
use Illuminate\Support\Facades\Storage;

class ReservaController extends Controller
{

    public function index(Request $request)
    {
        Carbon::setLocale('es');
        $alternativeView = $request->query('alternative', false);
        $alternativeView = $alternativeView == 1 ? true : false;
        $mobileView = $request->query('mobileview', '');
        $fechaActual     = Carbon::now()->startOfDay();
    

        if ($alternativeView) {

            $reservasQuery = Reserva::where('fecha_visita', '>=', Carbon::now()->startOfDay())
            ->with([
                'cliente',
                'visitas.ubicacion',
                'masajes',
                'programa',
                'venta',
            ])
            ->select('*')
            ->selectSub(
                DB::table('visitas')
                    ->whereColumn('visitas.id_reserva', 'reservas.id')
                    ->orderBy('id_ubicacion', 'asc')
                    ->limit(1)
                    ->select('id_ubicacion'),
                'first_id_ubicacion'
            )
            ->orderBy('fecha_visita', 'asc')
            ->orderBy('first_id_ubicacion', 'asc');

        }else{
            $reservasQuery = Reserva::where('fecha_visita', '>=', $fechaActual)
            ->with([
                'cliente',
                'visitas.ubicacion',
                'masajes',
                'programa',
                'venta',
            ])
            ->select('reservas.*')
            ->selectSub(
                DB::table('visitas')
                    ->whereColumn('visitas.id_reserva', 'reservas.id')
                    ->orderBy('horario_sauna', 'asc')
                    ->limit(1)
                    ->select('horario_sauna'),
                'first_horario_sauna'
            )
            ->orderBy('fecha_visita', 'asc')
            ->orderBy('first_horario_sauna', 'asc');
        }
    
        $reservas = $reservasQuery->get();
    
        $reservasPorDia = $reservas->groupBy(function ($reserva) {
            return Carbon::parse($reserva->fecha_visita)->format('d-m-Y');
        });
    
        // Paginación
        $perPage           = 1;
        $currentPage       = LengthAwarePaginator::resolveCurrentPage();
        $currentItems      = $reservasPorDia->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $reservasPaginadas = new LengthAwarePaginator($currentItems, $reservasPorDia->count(), $perPage, $currentPage);
        $reservasPaginadas->setPath(request()->url());

        //Reservas Para moviles
        $reservasMoviles = Reserva::where('fecha_visita', '>=', $fechaActual)
        ->with([
            'cliente',
            'visitas.ubicacion',
            'masajes',
            'programa',
            'venta',
        ])
        ->select('reservas.*')
        ->selectSub(
            DB::table('visitas')
                ->whereColumn('visitas.id_reserva', 'reservas.id')
                ->orderBy('horario_sauna', 'asc')
                ->limit(1)
                ->select('horario_sauna'),
            'first_horario_sauna'
        )
        ->orderBy('fecha_visita', 'asc')
        ->orderBy('first_horario_sauna', 'asc')
        ->get();

        $reservasDia = $reservasMoviles->groupBy(function ($reservamovil) {
            return Carbon::parse($reservamovil->fecha_visita)->format('d-m-Y');
        });

        $porPagina           = 1;
        $paginaActual       = LengthAwarePaginator::resolveCurrentPage();
        $itemsActuales      = $reservasDia->slice(($paginaActual - 1) * $porPagina, $porPagina)->all();
        $reservasMovilesPaginadas = new LengthAwarePaginator($itemsActuales, $reservasDia->count(), $porPagina, $paginaActual);
        $reservasMovilesPaginadas->setPath(request()->url());


        // Obtenemos la fecha seleccionada del formulario
        $fechaSeleccionada   = \Carbon\Carbon::now()->format('Y-m-d');
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
            return ! in_array($ubicacion->nombre, $ubicacionesOcupadas);
        })->values();

        // ===============================HORAS=SPA==============================================
        // Horarios disponibles de 10:00 a 18:30 SPA
        $horaInicio = new \DateTime('10:00');
        $horaFin    = new \DateTime('18:30');
        $intervalo  = new \DateInterval('PT30M');
        $horarios   = [];

        while ($horaInicio <= $horaFin) {
            $horarios[] = $horaInicio->format('H:i');
            $horaInicio->add($intervalo);
        }

        // Obtener horarios ocupados de la tabla 'visitas'
        $horariosOcupados = DB::table('visitas')
            ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
            ->where('reservas.fecha_visita', $fechaSeleccionada)
            ->pluck('visitas.horario_sauna')
            ->filter(function ($hora) {
                // Filtrar valores nulos o vacíos
                return ! is_null($hora) && $hora !== '';
            })
            ->map(function ($hora) {
                // Formatear solo los horarios válidos
                return \Carbon\Carbon::createFromFormat('H:i:s', $hora)->format('H:i');
            })
            ->toArray();

        // Filtrar horarios disponibles
        $horariosDisponibles = array_diff($horarios, $horariosOcupados);


                //=================================HORAS=MASAJES=========================================

        // Horarios disponibles de 10:20 a 19:00 con intervalos de 10 minutos entre sesiones de masaje
        $horaInicioMasajes = new \DateTime('10:20');
        $horaFinMasajes    = new \DateTime('19:00');
        $duracionMasaje    = new \DateInterval('PT30M'); // 30 minutos de duración
        $intervalos        = new \DateInterval('PT10M'); // 10 minutos de intervalos entre sesiones
        $horarios          = [];

        while ($horaInicioMasajes <= $horaFinMasajes) {
            $horarios[] = $horaInicioMasajes->format('H:i');
            $horaInicioMasajes->add($duracionMasaje);
            $horaInicioMasajes->add($intervalos);
        }

        // Obtener las horas de inicio ocupadas de la tabla 'visitas' para masajes
        $horariosOcupadosMasajes = DB::table('visitas')
            ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
            ->join('masajes as m', 'm.id_reserva', '=', 'reservas.id')
            ->where('reservas.fecha_visita', $fechaSeleccionada)
            ->whereNotNull('m.horario_masaje')
            ->select('m.horario_masaje', 'm.id_lugar_masaje')
            ->get()
            ->groupBy('id_lugar_masaje');

        // Procesar horarios ocupados
        $ocupadosPorLugar = [
            1 => [], // Containers
            2 => [], // Toldos
        ];

        foreach ($horariosOcupadosMasajes as $lugar => $visitas) {
            $ocupadosPorLugar[$lugar] = $visitas->pluck('horario_masaje')
                ->map(function ($hora) {
                    return \Carbon\Carbon::createFromFormat('H:i:s', $hora)->format('H:i');
                })
                ->toArray();
        }

        // Filtrar horarios disponibles por lugar
        $horariosDisponiblesMasajes = [
            1 => array_values(array_diff($horarios, $ocupadosPorLugar[1])), // Containers
            2 => array_values(array_diff($horarios, $ocupadosPorLugar[2])), // Toldos
        ];


    
        return view('themes.backoffice.pages.reserva.index', compact('reservasPaginadas', 'alternativeView', 'reservasMovilesPaginadas', 'mobileView', 'horariosDisponibles', 'horariosDisponiblesMasajes'));
    }


    public function create($cliente)
    {
        $this->authorize('create', User::class);
        $cliente   = Cliente::findOrFail($cliente);
        $programas = Programa::with('servicios')->get();
        $tipos     = TipoTransaccion::all();

        return view('themes.backoffice.pages.reserva.create', [
            'cliente'   => $cliente,
            'programas' => $programas,
            'tipos'     => $tipos,
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
            return ! in_array($ubicacion->nombre, $ubicacionesOcupadas);
        })->values();

        return response()->json($ubicaciones);
    }

    public function store(StoreRequest $request, Reserva $reserva)
    {
        $request->merge([
            'abono_programa'    => (int) str_replace(['$', '.', ','], '', $request->abono_programa),
            'cantidad_personas' => (int) str_replace(['$', '.', ','], '', $request->cantidad_personas),
            'total_pagar'       => (int) str_replace(['$', '.', ','], '', $request->total_pagar),
        ]);

        $masajesExtra         = null;
        $almuerzosExtra       = null;
        $cantidadMasajesExtra = null;

        // Verificar si el programa seleccionado incluye un masaje
        $programa = Programa::find($request->id_programa);

        // Buscar si el programa tiene un servicio de masaje
        $incluyeMasaje = $programa->incluye_masajes;

        try {

            // Iniciar la transacción
            DB::transaction(function () use ($request, &$reserva, $incluyeMasaje, &$masajesExtra, &$almuerzosExtra, &$cantidadMasajesExtra) {

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
                $filename  = null;

                if ($request->hasFile('imagen_abono')) {
                    $abono     = $request->file('imagen_abono');
                    $filename  = time() . '-' . $abono->getClientOriginalName();
                    $url_abono = 'temp/' . $filename; // Almacenamiento temporal
                    Storage::disk('imagen_abono')->put($url_abono, File::get($abono));
                }

                // Crear la venta relacionada con la reserva
                $venta = Venta::create([
                    'id_reserva'                => $reserva->id,
                    'abono_programa'            => $request->abono_programa,
                    'imagen_abono'              => $url_abono,
                    'id_tipo_transaccion_abono' => $request->tipo_transaccion,
                    'total_pagar'               => $request->total_pagar,
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
                        'id_venta'      => $venta->id,
                        'subtotal'      => 0,
                        'total_consumo' => 0,
                    ]);

                    $servicioMasaje = Servicio::whereIn('nombre_servicio', ['Masaje', 'Masajes', 'masaje', 'masajes'])->first();

                    if ($servicioMasaje) {
                        $subtotal             = 0;
                        $cantidadMasajesExtra = intval($request->input('cantidad_masajes_extra'));
                        $subtotalMasajes      = $servicioMasaje->valor_servicio * $cantidadMasajesExtra;
                        $subtotal             = $subtotalMasajes;

                        // Crear el detalle del servicio extra
                        DetalleServiciosExtra::create([
                            'cantidad_servicio' => $cantidadMasajesExtra,
                            'subtotal'          => $subtotalMasajes,
                            'id_consumo'        => $consumo->id,
                            'id_servicio_extra' => $servicioMasaje->id,
                        ]);

                        $consumo->subtotal += $subtotal;
                        $consumo->total_consumo += $subtotal;
                        $consumo->save();
                    }

                }

                if ($request->filled('agregar_almuerzos')) {

                    $almuerzosExtra = $request->filled('agregar_almuerzos');

                    if (! $consumo) {
                        $consumo = Consumo::create([
                            'id_venta'      => $venta->id,
                            'subtotal'      => 0,
                            'total_consumo' => 0,
                        ]);
                    }

                    $servicioAlmuerzo = Servicio::whereIn('nombre_servicio', ['Almuerzo', 'Almuerzos', 'almuerzo', 'almuerzos'])->first();

                    if ($servicioAlmuerzo) {
                        $subtotal               = 0;
                        $cantidadAlmuerzosExtra = intval($request->input('cantidad_personas'));
                        $subtotalAlmuerzos      = $servicioAlmuerzo->valor_servicio * $cantidadAlmuerzosExtra;
                        $subtotal               = $subtotalAlmuerzos;

                        // Crear el detalle del servicio extra
                        DetalleServiciosExtra::create([
                            'cantidad_servicio' => $cantidadAlmuerzosExtra,
                            'subtotal'          => $subtotalAlmuerzos,
                            'id_consumo'        => $consumo->id,
                            'id_servicio_extra' => $servicioAlmuerzo->id,
                        ]);

                        $consumo->subtotal += $subtotal;
                        $consumo->total_consumo += $subtotal;
                        $consumo->save();
                    }

                }
            });

            // Mostrar alerta de éxito
            // Alert::success('Éxito', 'Reserva realizada con éxito', 'Confirmar')->showConfirmButton();

            session()->put([
                'masajesExtra'         => $masajesExtra,
                'almuerzosExtra'       => $almuerzosExtra,
                'cantidadMasajesExtra' => $cantidadMasajesExtra,
            ]);

            // Redirigir fuera de la transacción
            return redirect()->route('backoffice.reserva.visitas.create', ['reserva' => $reserva->id])->with('success', 'Reserva realizada con éxito');

        } catch (\Error $e) {
            // Alert::error('Falló', 'Error: ' . $e, 'Confirmar')->showConfirmButton();
            return redirect()->back()->with('error', 'Error: ' . $e)->withInput();
        }

    }

    public function show(Reserva $reserva)
    {
        $reserva->load('venta.consumo.detallesConsumos.producto', 'venta.consumo.detalleServiciosExtra.servicio', 'menus', 'menus.productoEntrada', 'menus.productoFondo', 'menus.productoacompanamiento', 'masajes');

        $asignados = Asignacion::all();

        $servicios = Servicio::all();
        $visitas   = $reserva->visitas;
        $masajes   = null;

            if ($reserva->masajes->isNotEmpty()) {
                $masajes = $reserva->masajes;
            }



        return view('themes.backoffice.pages.reserva.show', [
            'reserva'   => $reserva,
            'servicios' => $servicios,
            'visitas'   => $visitas,
            'masajes'   => $masajes,
            'asignados' => $asignados
        ]);
    }

    public function showConsumoImage($id)
    {
        $reserva     = Reserva::findOrFail($id);
        $pagoConsumo = null;

        foreach ($reserva->venta->consumos as $consumo) {
            if ($consumo->pagosConsumos->where('id_consumo', $consumo->id)) {
                foreach ($consumo->pagosConsumos as $pago) {
                    $pagoConsumo = $pago;
                }
            }
        }

        // Verificar si el archivo de abono existe
        if (Storage::disk('imagen_consumo')->exists($pagoConsumo->imagen_transaccion)) {
            $file     = Storage::disk('imagen_consumo')->get($pagoConsumo->imagen_transaccion);
            $mimeType = Storage::disk('imagen_consumo')->mimeType($pagoConsumo->imagen_transaccion);

            return response($file, 200)->header('Content-Type', $mimeType);
        }

        return abort(404, 'Imagen de abono no encontrada');
    }

    public function showAbonoImage($id)
    {
        $reserva = Reserva::findOrFail($id);

        // Verificar si el archivo de abono existe
        if (Storage::disk('imagen_abono')->exists($reserva->venta->imagen_abono)) {
            $file     = Storage::disk('imagen_abono')->get($reserva->venta->imagen_abono);
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
            $file     = Storage::disk('imagen_diferencia')->get($reserva->venta->imagen_diferencia);
            $mimeType = Storage::disk('imagen_diferencia')->mimeType($reserva->venta->imagen_diferencia);

            return response($file, 200)->header('Content-Type', $mimeType);
        }

        // return abort(404, 'Imagen de diferencia no encontrada');
        return redirect('https://placehold.co/200x300');
    }

    public function edit(Reserva $reserva)
    {
        // $this->authorize('create', User::class);
        $cantidadExtraMasaje = null;
        $ventaId             = $reserva->venta->id;
        $consumo             = Consumo::where('id_venta', '=', $ventaId)->first();
        if (isset($consumo)) {
            $detalleServExtra = DetalleServiciosExtra::where('id_consumo', '=', $consumo->id)->first();
            if (isset($detalleServExtra)) {
                $cantidadExtraMasaje = $detalleServExtra->cantidad_servicio;
            }
        }
        $cliente   = $reserva->cliente;
        $venta     = $reserva->venta;
        $visita    = $reserva->visitas->first();
        $programas = Programa::with('servicios')->get();
        $tipos     = TipoTransaccion::all();
        // dd(!$reserva->programa->servicios->contains('nombre_servicio', 'Masaje') && $visita->horario_masaje);
        return view('themes.backoffice.pages.reserva.edit', [
            'reserva'        => $reserva,
            'venta'          => $venta,
            'cliente'        => $cliente,
            'programas'      => $programas,
            'tipos'          => $tipos,
            'visita'         => $visita,
            'cantidadMasaje' => $cantidadExtraMasaje,
        ]);
    }

    public function update(UpdateRequest $request, Reserva $reserva)
    {
        $masajesExtra   = null;
        $almuerzosExtra = null;
        $data           = $request->all();

        $original = DB::table('reservas')
            ->join('ventas as v', 'reservas.id', '=', 'v.id_reserva')
            ->where('reservas.id', $reserva->id)
            ->select('reservas.*', 'v.abono_programa', 'v.id_tipo_transaccion_abono as tipo_transaccion', 'v.total_pagar')
            ->first();

        $originalArray = (array) $original; // Convertir objeto en arreglo

        // Parsear y formatear la fecha_visita del original
        if (! empty($originalArray['fecha_visita'])) {
            $originalArray['fecha_visita'] = Carbon::parse($originalArray['fecha_visita'])->format('d-m-Y'); // Ajusta el formato según sea necesario
        }

        $changes = [];

        foreach ($data as $key => $value) {
            // Comprobar si el valor existe en los datos originales y si es diferente
            if (array_key_exists($key, $originalArray) && $originalArray[$key] != $value) {
                $changes[$key] = [
                    'original' => $originalArray[$key],
                    'new'      => $value,
                ];
            }
        }

        // Verificar si hubo cambios
        if (! $changes) {
            // Si no hay cambios, redirigir a otra ruta
            return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva->id])->with('info', 'No se realizaron cambios en la reserva.');
        }

        // Si hay cambios, continuar con la lógica normal
        try {
            DB::transaction(function () use ($request, &$reserva, $originalArray, &$masajesExtra, &$almuerzosExtra) {

                // dd($originalArray['fecha_visita'] ,$request->input('fecha_visita'));
                if ($originalArray['fecha_visita'] !== $request->input('fecha_visita')) {
                    $nuevaFecha = Carbon::createFromFormat('d-m-Y', $request->input('fecha_visita'))->format('Y-m-d');
                    // Guardar la fecha original de la reserva en el reagendamiento
                    $reagendamiento = Reagendamiento::create([
                        'fecha_original' => Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d'),
                        'nueva_fecha'    => $nuevaFecha,
                        'id_reserva'     => $reserva->id,
                    ]);
                    // Actualizar la reserva con la nueva fecha de visita
                    $reserva->fecha_visita = $nuevaFecha;
                };
                // Guardar los cambios en la reserva
                $reserva->save();

                // Verificar si el programa incluye masaje
                $programa      = Programa::find($request->id_programa);
                $incluyeMasaje = $programa->servicios()
                    ->whereIn('nombre_servicio', ['Masaje', 'Masajes', 'masaje', 'masajes'])
                    ->exists();

                $reserva->update([
                    'cantidad_personas' => $request->cantidad_personas,
                    'observacion'       => $request->observacion,
                    'id_programa'       => $request->id_programa,
                ]);
                // Actualizar cantidad_masajes si el programa incluye masaje
                if ($incluyeMasaje) {
                    $reserva->update(['cantidad_masajes' => $request->cantidad_personas]);
                } else {
                    $reserva->update(['cantidad_masajes' => null]);
                }

                // Actualizar la venta relacionada con la reserva
                $venta = $reserva->venta ?? new Venta();

                // Manejo de la imagen de abono
                $url_abono = null;
                if ($request->hasFile('imagen_abono_boton')) {
                    if (! empty($venta->imagen_abono) && Storage::disk('imagen_abono')->exists($venta->imagen_abono)) {
                        Storage::disk('imagen_abono')->delete($venta->imagen_abono);
                    }
                    $abono     = $request->file('imagen_abono_boton');
                    $filename  = time() . '-' . $abono->getClientOriginalName();
                    $url_abono = 'temp/' . $filename;
                    Storage::disk('imagen_abono')->put($url_abono, File::get($abono));
                }

                $venta->fill([
                    'abono_programa'            => $request->abono_programa,
                    'imagen_abono'              => $url_abono ?? $venta->imagen_abono,
                    'id_tipo_transaccion_abono' => $request->tipo_transaccion,
                    'total_pagar'               => $request->total_pagar,
                ])->save();

                // Mover la imagen a su ubicación final, si es necesario
                if ($url_abono) {
                    $finalPath = '/' . $filename;
                    Storage::disk('imagen_abono')->move('temp/' . $filename, $finalPath);
                    $venta->update(['imagen_abono' => $finalPath]);
                }

                // Manejar los servicios extra
                if ($request->filled('cantidad_masajes_extra')) {
                    $masajesExtra = $request->filled('cantidad_masajes_extra');
                    $this->manipularMasajesExtra($request, $venta);
                }

                if ($request->filled('agregar_almuerzos')) {
                    $almuerzosExtra = $request->filled('agregar_almuerzos');
                    $this->manipularAlmuerzosExtra($request, $venta);
                }
            });

            session()->put([
                'masajesExtra'   => $masajesExtra,
                'almuerzosExtra' => $almuerzosExtra,
            ]);

            return redirect()->route('backoffice.reserva.visitas.edit', ['reserva' => $reserva, 'visita' => $reserva->visitas->first()])->with('success', 'La reserva fue actualizada exitosamente.');
        } catch (\Error $e) {
            return redirect()->back()->with('error', 'Ocurrió un error al actualizar la reserva. ' . $e);
        }
    }

    private function manipularMasajesExtra($request, $venta)
    {
        $servicioMasaje = Servicio::whereIn('nombre_servicio', ['Masaje', 'Masajes', 'masaje', 'masajes'])->first();
        $consumo        = Consumo::where('id_venta', '=', $venta->id)->first();

        if ($servicioMasaje) {
            $cantidadMasajesExtra = intval($request->input('cantidad_masajes_extra'));
            $subtotalMasajes      = $servicioMasaje->valor_servicio * $cantidadMasajesExtra;

            DetalleServiciosExtra::updateOrCreate(
                [
                    'id_consumo'        => $consumo->id,
                    'id_servicio_extra' => $servicioMasaje->id,
                ],
                [
                    'cantidad_servicio' => $cantidadMasajesExtra,
                    'subtotal'          => $subtotalMasajes,
                ]
            );
        }
    }

    private function manipularAlmuerzosExtra($request, $venta)
    {
        $servicioAlmuerzo = Servicio::whereIn('nombre_servicio', ['Almuerzo', 'Almuerzos', 'almuerzo', 'almuerzos'])->first();
        if ($servicioAlmuerzo) {
            $cantidadAlmuerzosExtra = intval($request->input('cantidad_personas'));
            $subtotalAlmuerzos      = $servicioAlmuerzo->valor_servicio * $cantidadAlmuerzosExtra;

            DetalleServiciosExtra::updateOrCreate(
                [
                    'id_consumo'        => $venta->consumo->id,
                    'id_servicio_extra' => $servicioAlmuerzo->id,
                ],
                [
                    'cantidad_servicio' => $cantidadAlmuerzosExtra,
                    'subtotal'          => $subtotalAlmuerzos,
                ]
            );
        }
    }

    public function destroy(Reserva $reserva)
    {
        //
    }

    public function generarPDF(Reserva $reserva)
    {
        $reserva->load('venta.consumo.detallesConsumos.producto', 'venta.consumo.detalleServiciosExtra.servicio', 'menus', 'menus.productoEntrada', 'menus.productoFondo', 'menus.productoacompanamiento', 'masajes');

        $total   = 0;
        $propina = 'No Aplica';
        $visita  = $reserva->visitas->first();
        $menus           = $reserva->menus;
        $consumo        = $reserva->venta->consumo;
        $idConsumo       = null;
        $cantidadPropina = null;
        $diferencia = 0;
        // dd($menus);

        $diferencia = ($reserva->programa->valor_programa * $reserva->cantidad_personas) - $reserva->venta->abono_programa;

        if (is_null($consumo)) {
            $propina = 'No Aplica';
        } else {

                $idConsumo = $consumo->id;
                foreach ($consumo->detallesConsumos as $detalles) {
                    if ($detalles->genera_propina) {
                        $total   = $consumo->total_consumo;
                        $propina = 'Si';
                    } else {
                        $total   = $consumo->subtotal;
                        $propina = 'No';
                    }
                }
            

                $cantidadPropina = Propina::where('propinable_id', $idConsumo)
                ->where('propinable_type', Consumo::class)
                ->first();

            if ($cantidadPropina) {

                $cantidadPropina = $cantidadPropina->cantidad;
            }

        }

        $saveName = str_replace(' ', '_', $reserva->cliente->nombre_cliente);

        $data = [
            'nombre'        => $reserva->cliente->nombre_cliente,
            'numero'        => $reserva->cliente->whatsapp_cliente,
            'observacion'   => $reserva->observacion ? $reserva->observacion : 'Sin Observaciones',
            'fecha_visita'  => $reserva->fecha_visita,
            'programa'      => $reserva->programa->nombre_programa,
            'personas'      => $reserva->cantidad_personas,
            'menus'         => $menus,
            'consumo'      => $consumo,
            'venta'         => $reserva->venta,
            'total'         => $total,
            'propina'       => $propina,
            'propinaPagada' => $cantidadPropina ? $cantidadPropina : 'No Aplica',
            'diferencia' => $diferencia
        ];

        // dd($data);
        $pdf = Pdf::loadView('pdf.venta.viewPDF', $data);
        // return $pdf->download('factura.pdf');
        return $pdf->stream('Detalle_Venta' . '_' . $saveName . '_' . $reserva->fecha_visita . '.pdf');

    }

    public function generarPDFConsumo(Reserva $reserva)
    {
        $reserva->load('venta.consumo.detallesConsumos.producto', 'venta.consumo.detalleServiciosExtra.servicio', 'menus', 'menus.productoEntrada', 'menus.productoFondo', 'menus.productoacompanamiento', 'masajes');

        $total   = 0;
        $propina = 'No Aplica';
        $visita  = $reserva->visitas->first();
        $consumo        = $reserva->venta->consumo;
        $idConsumo       = null;
        $cantidadPropina = null;

        if (is_null($consumo)) {
            $propina = 'No Aplica';
        } else {
                $idConsumo = $consumo->id;
                foreach ($consumo->detallesConsumos as $detalles) {
                    if ($detalles->genera_propina) {
                        $total   = $consumo->total_consumo;
                        $propina = 'Si';
                    } else {
                        $total   = $consumo->subtotal;
                        $propina = 'No';
                    }
                }

            // $cantidadPropina = DB::table('propinas')
            //     ->where('id_consumo', '=', $idConsumo)
            //     ->first();

            $cantidadPropina = Propina::where('propinable_id', $idConsumo)
                ->where('propinable_type', Consumo::class)
                ->first();

            if ($cantidadPropina) {

                $cantidadPropina = $cantidadPropina->cantidad;
            }

        }

        $saveName = str_replace(' ', '_', $reserva->cliente->nombre_cliente);

        $dataConsumo = [
            'nombre'        => $reserva->cliente->nombre_cliente,
            'numero'        => $reserva->cliente->whatsapp_cliente,
            'observacion'   => $reserva->observacion ? $reserva->observacion : 'Sin Observaciones',
            'fecha_visita'  => $reserva->fecha_visita,
            'programa'      => $reserva->programa->nombre_programa,
            'personas'      => $reserva->cantidad_personas,
            'consumo'      => $consumo,
            'venta'         => $reserva->venta,
            'total'         => $total,
            'propina'       => $propina,
            'propinaPagada' => $cantidadPropina ? $cantidadPropina : 'No Aplica',
        ];

        $pdf = PDF::loadView('pdf.consumo_separado.viewPDF', $dataConsumo);
        return $pdf->stream('Detalle_Consumo' . '_' . $saveName . '_' . $reserva->fecha_visita . '.pdf');
    }

    public function indexall()
    {
        Carbon::setLocale('es');

        // Vista anterior
        $currentMonth = Carbon::now()->month;
        $currentYear  = Carbon::now()->year;

        $reservasPorMes = Reserva::with(['cliente', 'visitas', 'programa.servicios', 'venta'])
            ->orderBy('fecha_visita')
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->fecha_visita)->format('Y-m');
            });

        return view('themes.backoffice.pages.reserva.index_all', compact('reservasPorMes'));
    }

    public function indexReserva()
    {
        Carbon::setLocale('es');
    
        $fechaFiltro = request('fecha'); // formato esperado: Y-m-d
    
        $reservas = Reserva::with([
                'cliente',
                'visitas',
                'masajes',
                'visitas.ubicacion',
                'menus',
                'programa',
                'venta',
            ])
            ->when($fechaFiltro, function ($query, $fechaFiltro) {
                return $query->whereDate('fecha_visita', Carbon::parse($fechaFiltro)->format('Y-m-d'));
            })
            ->orderBy('fecha_visita', 'asc')
            ->get();
    
        $reservasPorDia = $reservas->groupBy(function ($reserva) {
            return Carbon::parse($reserva->fecha_visita)->format('d-m-Y');
        });
    
        return view('themes.backoffice.pages.reserva.index_registro', [
            'reservasPaginadas' => $reservasPorDia,
            'fechaF' => $fechaFiltro
        ]);
    }

    public function indexallRegistros()
    {
        Carbon::setLocale('es');
        
        $currentMonth = Carbon::now()->month;
        $currentYear  = Carbon::now()->year;

        
        $reservasPorMes = Reserva::with(['cliente', 'visitas', 'programa.servicios', 'venta', 'menus', 'masajes', 'venta.consumo'])
            ->orderBy('fecha_visita')
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->fecha_visita)->format('Y-m');
            });

        return view('themes.backoffice.pages.reserva.all_registro', [
            'reservasPorMes' => $reservasPorMes,
        ]);
    }


    public function masaje(Reserva $reserva)
    {
        $masajes = $reserva->masajes;
        // dd($masajes);
        $masajesExtra = null;
        $serviciosDisponibles = $reserva->programa->servicios->pluck('nombre_servicio')->toArray();

        if (in_array('Masaje', $serviciosDisponibles)) {
            $masajesExtra = false;
        } else {
            $masajesExtra = isset($masajes);
        }
        
        // dd($masajesExtra);

        $fechaSeleccionada = \Carbon\Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d');

        // Horarios disponibles de 10:20 a 19:00 con intervalos de 10 minutos entre sesiones de masaje
        $horaInicioMasajes = new \DateTime('10:20');
        $horaFinMasajes    = new \DateTime('19:00');
        $duracionMasaje    = new \DateInterval('PT30M'); // 30 minutos de duración
        $intervalos        = new \DateInterval('PT10M'); // 10 minutos de intervalos entre sesiones
        $horarios          = [];
        
        while ($horaInicioMasajes <= $horaFinMasajes) {
            $horarios[] = $horaInicioMasajes->format('H:i');
            $horaInicioMasajes->add($duracionMasaje);
            $horaInicioMasajes->add($intervalos);
        }

        // Obtener las horas de inicio ocupadas de la tabla 'visitas' para masajes
        $horariosOcupadosMasajes = DB::table('masajes')
        ->join('reservas', 'masajes.id_reserva', '=', 'reservas.id')
        ->where('reservas.fecha_visita', $fechaSeleccionada)
        ->whereNotNull('masajes.horario_masaje')
        ->select('masajes.id','masajes.horario_masaje', 'masajes.id_lugar_masaje', 'masajes.persona', 'masajes.tipo_masaje')
        ->get()
        ->groupBy('id_lugar_masaje');


        // Procesar horarios ocupados
        $ocupadosPorLugar = [
            1 => [], // Containers
            2 => [], // Toldos
        ];

        foreach ($horariosOcupadosMasajes as $lugar => $horariosMasajes) {
            $ocupadosPorLugar[$lugar] = $horariosMasajes->pluck('horario_masaje')
                ->map(function ($hora) {
                    return \Carbon\Carbon::createFromFormat('H:i:s', $hora)->format('H:i');
                })
                ->toArray();
        }

        // Filtrar horarios disponibles por lugar
        $horariosDisponiblesMasajes = [
            1 => array_values(array_diff($horarios, $ocupadosPorLugar[1])), // Containers
            2 => array_values(array_diff($horarios, $ocupadosPorLugar[2])), // Toldos
        ];

        return view('themes.backoffice.pages.reserva.masaje.edit',[
            'masajes'       => $masajes,
            'reserva'       => $reserva,
            'lugares'       => LugarMasaje::all(),
            'horasMasaje'   => $horariosDisponiblesMasajes,
            'servicios'     => $serviciosDisponibles,
            'masajesExtra'  => $masajesExtra,
        ]);

    }

    public function masaje_update(Request $request, Reserva $reserva) 
    {

        $request->validate([
            'masajes.*.horario_masaje' => 'required|string',
            'masajes.*.tipo_masaje' => 'required|string|in:Relajante,Descontracturante',
            'masajes.*.id_lugar_masaje' => 'required|exists:lugares_masajes,id',
        ]);
    
        try {

            foreach ($request->masajes as $id => $datos) {

                $masaje = Masaje::findOrFail($id);
    
                $masaje->update([
                    'horario_masaje' => $datos['horario_masaje'],
                    'tipo_masaje' => $datos['tipo_masaje'],
                    'id_lugar_masaje' => $datos['id_lugar_masaje'],
                ]);
            }
    
            return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva])->with('success', 'Masajes actualizados correctamente.');
    
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ocurrió un error al actualizar los masajes.');
        }
    }

    public function menu(Reserva $reserva)
    {
        $servicios = $reserva->programa->servicios->pluck('nombre_servicio')->toArray();
        $menus = $reserva->menus;

        $almuerzosExtra = null;

        if (in_array('Almuerzo', $servicios)) {
            $almuerzosExtra = false;
        } else {
            $almuerzosExtra = isset($menus);
        }

                // Obtener productos de tipo "entrada"
                $entradas = Producto::whereHas('tipoProducto', function ($query) {
                    $query->where('nombre', 'entrada');
                })->get();
        
                // Obtener productos de tipo "fondo"
                $fondos = Producto::whereHas('tipoProducto', function ($query) {
                    $query->where('nombre', 'fondo');
                })->get();
        
                // Obtener productos de tipo "acompañamiento"
                $acompañamientos = Producto::whereHas('tipoProducto', function ($query) {
                    $query->where('nombre', 'acompañamiento');
                })->get();

        return view('themes.backoffice.pages.reserva.menu.edit', [
            'reserva'           => $reserva,
            'servicios'         => $servicios,
            'menus'             => $menus,
            'entradas'          => $entradas,
            'fondos'            => $fondos,
            'acompañamientos'   => $acompañamientos,
            'almuerzosExtra'    => $almuerzosExtra,
        ]);
    }

    public function menu_update(Request $request, Reserva $reserva)
    {
        $request->validate([
            'menus.*.id_producto_entrada' => 'required|integer|exists:productos,id',
            'menus.*.id_producto_fondo' => 'required|integer|exists:productos,id',
            'menus.*.id_producto_acompanamiento' => 'nullable|integer|exists:productos,id',
            'menus.*.alergias' => 'nullable|string',
            'menus.*.observacion' => 'nullable|string',
        ]);


        try {
            
            foreach ($request->menus as $id => $datos) {
                $menu = Menu::findOrFail($id);

                $menu->update([
                    'id_producto_entrada'           => $datos['id_producto_entrada'],
                    'id_producto_fondo'             => $datos['id_producto_fondo'],
                    'id_producto_acompanamiento'    => $datos['id_producto_acompanamiento'],
                    'alergias'                      => $datos['alergias'],
                    'observacion'                   => $datos['observacion'],
                ]);
            }

            return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva])->with('success', 'Menús actualizados correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ocurrió un error al actualizar los menús. '.$e->getMessage());
        }
    }
}
