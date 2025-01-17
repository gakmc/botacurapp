<?php
namespace App\Http\Controllers;

use App\Http\Requests\Visita\StoreRequest;
use App\Http\Requests\Visita\UpdateRequest;
use App\LugarMasaje;
use App\Mail\RegistroReservaMailable;
use App\Masaje;
use App\Menu;
use App\Producto;
use App\Reserva;
use App\Ubicacion;
use App\Visita;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RealRashid\SweetAlert\Facades\Alert;

class VisitaController extends Controller
{
    public function index()
    {
        // Asignacion de dias Hoy y Mañana
        $hoy    = Carbon::today();
        $manana = Carbon::tomorrow();

        // Filtrar las reservas que tienen visitas y cuya fecha de visita es hoy o mañana
        $reservas = Reserva::with('visitas', 'cliente', 'programa', 'user')
            ->whereBetween('fecha_visita', [$hoy, $manana])
            ->get();

        // Filtrar por visitas de Hoy
        $reservasHoy = $reservas->filter(function ($reserva) use ($hoy) {
            return Carbon::parse($reserva->fecha_visita)->isSameDay($hoy);
        });

        // Filtrar por visitas de Mañana
        $reservasManana = $reservas->filter(function ($reserva) use ($manana) {
            return Carbon::parse($reserva->fecha_visita)->isSameDay($manana);
        });

        //Retorno de la vista
        return view('themes.backoffice.pages.visita.index', [
            'reservasHoy'    => $reservasHoy,
            'reservasManana' => $reservasManana,
            //Reservas para la relacion con visitas
            // 'reservas' => Reserva::with('cliente', 'programa', 'user')->get(),
        ]);
    }

    public function create($reserva)
    {
        // session()->put('masajesExtra',true);
        // session()->put('cantidadMasajesExtra',3);
        // dd(session()->get('cantidadMasajesExtra'));

        $masajesExtra         = session()->get('masajesExtra');
        $almuerzosExtra       = session()->get('almuerzosExtra');
        $cantidadMasajesExtra = session()->get('cantidadMasajesExtra');

        $reserva              = Reserva::findOrFail($reserva);
        $serviciosDisponibles = $reserva->programa->servicios->pluck('nombre_servicio')->toArray();

        // Obtenemos la fecha seleccionada del formulario
        $fechaSeleccionada   = \Carbon\Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d');
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
            ->map(function ($hora) {
                return \Carbon\Carbon::createFromFormat('H:i:s', $hora)->format('H:i');
            })
            ->toArray();

        // Filtrar horarios disponibles
        $horariosDisponiblesSPA = array_diff($horarios, $horariosOcupados);

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
            ->join('masajes as m', 'm.id_visita', '=', 'visitas.id')
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

        // // Filtrar horarios disponibles (ajusta si ocupas rangos completos)
        // $horariosDisponiblesMasajes = array_diff($horarios, $horariosOcupadosMasajes);

        // Obtener productos de tipo "entrada"
        $entradas = Producto::whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'entrada');
        })->get();

        // Obtener productos de tipo "fondo"
        $fondos = Producto::whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'fondo');
        })->get();

        // Obtener productos de tipo "postre"
        $acompañamientos = Producto::whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'acompañamiento');
        })->get();

        return view('themes.backoffice.pages.visita.create', [
            'reserva'         => $reserva,
            'ubicaciones'     => $ubicaciones,
            'lugares'         => LugarMasaje::all(),
            'servicios'       => $serviciosDisponibles,
            'horarios'        => $horariosDisponiblesSPA,
            'horasMasaje'     => $horariosDisponiblesMasajes,
            'entradas'        => $entradas,
            'fondos'          => $fondos,
            'acompañamientos' => $acompañamientos,
            'masajesExtra'    => $masajesExtra,
            'almuerzosExtra'  => $almuerzosExtra,
        ]);
    }

    public function createOLD($reserva)
    {
        $masajesExtra   = session()->get('masajesExtra');
        $almuerzosExtra = session()->get('almuerzosExtra');

        $reserva              = Reserva::findOrFail($reserva);
        $serviciosDisponibles = $reserva->programa->servicios->pluck('nombre_servicio')->toArray();

        // Obtenemos la fecha seleccionada del formulario
        $fechaSeleccionada   = \Carbon\Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d');
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
            ->map(function ($hora) {
                return \Carbon\Carbon::createFromFormat('H:i:s', $hora)->format('H:i');
            })
            ->toArray();

        // Filtrar horarios disponibles
        $horariosDisponiblesSPA = array_diff($horarios, $horariosOcupados);

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
            ->where('reservas.fecha_visita', $fechaSeleccionada)
            ->pluck('visitas.horario_masaje')
            ->filter(function ($hora) {
                return ! is_null($hora); // Filtra valores nulos
            })
            ->map(function ($hora) {
                return \Carbon\Carbon::createFromFormat('H:i:s', $hora)->format('H:i');
            })
            ->toArray();

        // Filtrar horarios disponibles (ajusta si ocupas rangos completos)
        $horariosDisponiblesMasajes = array_diff($horarios, $horariosOcupadosMasajes);

        // Obtener productos de tipo "entrada"
        $entradas = Producto::whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'entrada');
        })->get();

        // Obtener productos de tipo "fondo"
        $fondos = Producto::whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'fondo');
        })->get();

        // Obtener productos de tipo "postre"
        $acompañamientos = Producto::whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'acompañamiento');
        })->get();

        return view('themes.backoffice.pages.visita.create', [
            'reserva'         => $reserva,
            'ubicaciones'     => $ubicaciones,
            'lugares'         => LugarMasaje::all(),
            'servicios'       => $serviciosDisponibles,
            'horarios'        => $horariosDisponiblesSPA,
            'horasMasaje'     => $horariosDisponiblesMasajes,
            'entradas'        => $entradas,
            'fondos'          => $fondos,
            'acompañamientos' => $acompañamientos,
            'masajesExtra'    => $masajesExtra,
            'almuerzosExtra'  => $almuerzosExtra,
        ]);
    }

    public function store(StoreRequest $request, Reserva $reserva)
    {
        if (session()->get('cantidadMasajesExtra') !== null) {
            $personas = session()->get('cantidadMasajesExtra');
        } elseif ($reserva->cantidad_masajes !== null) {
            $personas = $reserva->cantidad_masajes;
        } else {
            $personas = $reserva->cantidad_personas;
        }

        $visita         = null;
        $cliente        = null;
        $programa       = $reserva->programa;
        $almuerzosExtra = session()->get('almuerzosExtra');

        $almuerzoIncluido = $programa->servicios->pluck('nombre_servicio')->toArray();

        try {
            DB::transaction(function () use ($request, &$reserva, &$visita, &$cliente, $almuerzoIncluido, $almuerzosExtra, $personas) {

                $cliente = $reserva->cliente;

                // Caso 1: Solo SPA (sin masajes)
                if (! $request->has('masajes') && ! $request->has('horario_masaje') && $request->has('horario_sauna')) {
                    // Convertir horario_sauna a objeto Carbon
                    $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

                    // Crear una visita con solo SPA
                    $visita = Visita::create([
                        'id_reserva'      => $reserva->id,
                        'horario_sauna'   => $horarioSauna,  // Horario del SPA
                        'horario_tinaja'  => $horarioTinaja, // Horario de tinaja
                        'horario_masaje'  => null,           // No hay masajes
                        'tipo_masaje'     => null,           // No hay masajes
                        'id_ubicacion'    => $request->input('id_ubicacion'),
                        'id_lugar_masaje' => null, // No hay masajes
                        'trago_cortesia'  => $request->input('trago_cortesia'),
                        'observacion'     => $request->input('observacion'),
                    ]);
                }

                // Caso 2: 1 SPA + 1 horario Masaje
                if ($request->has('horario_masaje') && $request->has('horario_sauna')) {
                    // Convertir horario_sauna a objeto Carbon
                    $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);
                    $horarioMasaje = Carbon::createFromFormat('H:i', $request->input('horario_masaje'));

                    // Crear una visita con solo SPA
                    $visita = Visita::create([
                        'id_reserva'     => $reserva->id,
                        'horario_sauna'  => $horarioSauna,  // Horario del SPA
                        'horario_tinaja' => $horarioTinaja, // Horario de tinaja
                        'id_ubicacion'   => $request->input('id_ubicacion'),
                        'trago_cortesia' => $request->input('trago_cortesia'),
                        'observacion'    => $request->input('observacion'),
                    ]);

                    for ($i = 1; $i <= $personas; $i++) {
                        $masaje = Masaje::create([
                            'horario_masaje'  => $horarioMasaje, // Horario de masaje
                            'tipo_masaje'     => $request->input('tipo_masaje'),
                            'id_lugar_masaje' => $request->input('id_lugar_masaje'),
                            'persona'         => $i,
                            'id_visita'       => $visita->id,
                        ]);
                    }

                }

                // Caso 3: 1 horario SPA con arreglo de masajes
                if ($request->has('masajes') && $request->has('horario_sauna')) {
                    // Obtener horario de sauna
                    $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

                    // Inicializar variables
                    $masajes               = $request->input('masajes');
                    $contadorPersonas      = 1; // Contador de personas que reciben masaje
                    $maxPersonasPorHorario = 2; // Máximo de personas por cada horario de masaje
                    $totalMasajes          = $personas;

                    // Crear la visita una sola vez
                    $visita = Visita::create([
                        'id_reserva'     => $reserva->id,
                        'horario_sauna'  => $horarioSauna,
                        'horario_tinaja' => $horarioTinaja,
                        'id_ubicacion'   => $request->input('id_ubicacion'),
                        'trago_cortesia' => $request->input('trago_cortesia'),
                        'observacion'    => $request->input('observacion'),
                    ]);

                    // Procesar los masajes
                    foreach ($masajes as $index => $horario) {
                        for ($i = 1; $i <= $maxPersonasPorHorario; $i++) {
                            if ($contadorPersonas > $totalMasajes) {
                                break;
                            }

                            $masaje = Masaje::create([
                                'horario_masaje'  => Carbon::createFromFormat('H:i', $horario['horario_masaje']),
                                'tipo_masaje'     => $horario['tipo_masaje'],
                                'id_lugar_masaje' => $horario['id_lugar_masaje'] ?? null,
                                'persona'         => $contadorPersonas,
                                'id_visita'       => $visita->id,
                            ]);
                            $contadorPersonas++;

                        }
                    }
                }

                // Caso 4: Arreglos de SPA sin masajes
                if (! $request->has('masajes') && $request->has('spas')) {
                    foreach ($request->input('spas') as $indexSpa => $spa) {
                        // Validar que el horario_sauna exista en el arreglo actual
                        if (isset($spa['horario_sauna'])) {
                            $horarioSauna  = Carbon::createFromFormat('H:i', $spa['horario_sauna']);
                            $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

                            // Crear una visita para cada SPA
                            $visita = Visita::create([
                                'id_reserva'     => $reserva->id,
                                'horario_sauna'  => $horarioSauna,
                                'horario_tinaja' => $horarioTinaja,
                                'id_ubicacion'   => $request->input('id_ubicacion'),
                                'trago_cortesia' => $request->input('trago_cortesia'),
                                'observacion'    => $request->input('observacion'),
                            ]);

                        }
                    }
                }

                // Caso 5: Arreglos de SPA y masajes
                if ($request->has('masajes') && $request->has('spas')) {
                    // Inicializar variables
                    $masajes               = $request->input('masajes');
                    $contadorPersonas      = 1; // Contador de personas que reciben masaje
                    $maxPersonasPorHorario = 2; // Máximo de personas por cada horario de masaje
                    $totalMasajes          = $personas;

                    //Procesar los horarios SPA
                    foreach ($request->input('spas') as $indexSpa => $spa) {
                        // Validar que el horario_sauna exista en el arreglo actual
                        if (isset($spa['horario_sauna'])) {
                            $horarioSauna  = Carbon::createFromFormat('H:i', $spa['horario_sauna']);
                            $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

                            // Crear una visita para cada SPA
                            $visita = Visita::create([
                                'id_reserva'     => $reserva->id,
                                'horario_sauna'  => $horarioSauna,
                                'horario_tinaja' => $horarioTinaja,
                                'id_ubicacion'   => $request->input('id_ubicacion'),
                                'trago_cortesia' => $request->input('trago_cortesia'),
                                'observacion'    => $request->input('observacion'),
                            ]);

                        }
                    }

                    // Procesar los masajes
                    foreach ($masajes as $index => $horario) {
                        for ($i = 1; $i <= $maxPersonasPorHorario; $i++) {
                            if ($contadorPersonas > $totalMasajes) {
                                break;
                            }

                            $masaje = Masaje::create([
                                'horario_masaje'  => Carbon::createFromFormat('H:i', $horario['horario_masaje']),
                                'tipo_masaje'     => $horario['tipo_masaje'],
                                'id_lugar_masaje' => $horario['id_lugar_masaje'] ?? null,
                                'persona'         => $contadorPersonas,
                                'id_visita'       => $visita->id,
                            ]);
                            $contadorPersonas++;

                        }
                    }
                }

                // Menus
                if (!in_array('Almuerzo', $almuerzoIncluido) && !$almuerzosExtra) {

                } else {

                    foreach ($request->menus as $menu) {
                        Menu::create([
                            'id_visita'                  => $visita->id,
                            'id_producto_entrada'        => $menu['id_producto_entrada'],
                            'id_producto_fondo'          => $menu['id_producto_fondo'],
                            'id_producto_acompanamiento' => $menu['id_producto_acompanamiento'],
                            'alergias'                   => $menu['alergias'],
                            'observacion'                => $menu['observacion'],
                        ]);
                    }
                }

            });

            if ($cliente && $visita) {
                Mail::to($cliente->correo)->send(new RegistroReservaMailable($visita, $reserva, $cliente, $programa));
            }

            Alert::success('Éxito', 'Se ha generado la visita')->showConfirmButton();

            session()->forget(['masajesExtra', 'almuerzosExtra']);

            return redirect()->route('backoffice.reserva.show', ['reserva' => $request->input('id_reserva')]);

        } catch (\Exception $e) {
            Alert::error('Error', 'Ocurrió un problema al generar la visita. Intente nuevamente. Error: ' . $e)->showConfirmButton();
            return redirect()->back()->withInput();
        }

    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return number_format($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }

    public function show(Visita $visitum)
    {
        dd($visitum->reserva->programa->servicios()->whereIn('nombre_servicio', ['Sauna', 'Saunas', 'sauna', 'saunas'])->exists());
    }

    public function edit(Reserva $reserva, Visita $visita)
    {
        // session()->put([
        //     'masajesExtra'=>true,
        // ]);

        // session()->forget(['masajesExtra']);
        $visitas        = $reserva->visitas;
        $menus          = $visitas->last()->menus;
        $masajesExtra   = session()->get('masajesExtra');
        $almuerzosExtra = session()->get('almuerzosExtra');

        $serviciosDisponibles = $reserva->programa->servicios->pluck('nombre_servicio')->toArray();

        // Obtenemos la fecha seleccionada del formulario
        $fechaSeleccionada   = \Carbon\Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d');
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
            ->map(function ($hora) {
                return \Carbon\Carbon::createFromFormat('H:i:s', $hora)->format('H:i');
            })
            ->toArray();

        // Filtrar horarios disponibles
        $horariosDisponiblesSPA = array_diff($horarios, $horariosOcupados);

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
            ->where('reservas.fecha_visita', $fechaSeleccionada)
            ->whereNotNull('visitas.horario_masaje')
            ->select('visitas.horario_masaje', 'visitas.id_lugar_masaje')
            ->get()
            ->groupBy('id_lugar_masaje');

        // Procesar horarios ocupados
        $ocupadosPorLugar = [
            1 => [], // Containers
            2 => [], // Toldos
        ];

        foreach ($horariosOcupadosMasajes as $lugar => $visitasAgendadas) {
            $ocupadosPorLugar[$lugar] = $visitasAgendadas->pluck('horario_masaje')
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

        // // Filtrar horarios disponibles (ajusta si ocupas rangos completos)
        // $horariosDisponiblesMasajes = array_diff($horarios, $horariosOcupadosMasajes);

        // Obtener productos de tipo "entrada"
        $entradas = Producto::whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'entrada');
        })->get();

        // Obtener productos de tipo "fondo"
        $fondos = Producto::whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'fondo');
        })->get();

        // Obtener productos de tipo "postre"
        $acompañamientos = Producto::whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'acompañamiento');
        })->get();

        return view('themes.backoffice.pages.visita.edit', [
            'visita'          => $visita,
            'visitas'         => $visitas,
            'reserva'         => $reserva,
            'menus'           => $menus,
            'ubicaciones'     => $ubicaciones,
            'lugares'         => LugarMasaje::all(),
            'servicios'       => $serviciosDisponibles,
            'horarios'        => $horariosDisponiblesSPA,
            'horasMasaje'     => $horariosDisponiblesMasajes,
            'entradas'        => $entradas,
            'fondos'          => $fondos,
            'acompañamientos' => $acompañamientos,
            'masajesExtra'    => $masajesExtra,
            'almuerzosExtra'  => $almuerzosExtra,
        ]);

    }

    public function update(UpdateRequest $request, Reserva $reserva, Visita $visita)
    {
        $visitas      = $reserva->visitas;
        $menus        = $visitas->last()->menus;
        $idVisitaMenu = $visitas->last()->id;
        $menuIds      = [];

        try {
            DB::transaction(function () use ($request, &$reserva, &$visitas, &$menus, &$menuIds, $idVisitaMenu, &$visita) {
                $horariosSpa = $request->input('spas'); // Obtener los horarios SPA del request
                if ($request->has('masajes')) {
                    $masajes      = $request->input('masajes'); // Obtener los horarios de masajes del request
                    $totalMasajes = count($masajes);
                }

                if ($request->has('menus')) {
                    $menusRequest = $request->input('menus'); // Obtener los menús del request
                }

                $contadorMasajes = 0;

                // dd($request);
                // Caso 1: Solo SPA (sin masajes)
                if (! $request->has('masajes') && ! $request->has('horario_masaje') && $request->has('horario_sauna')) {
                    // Convertir horario_sauna a objeto Carbon
                    $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

                    // Crear una visita con solo SPA
                    $visita->update([
                        'id_reserva'      => $reserva->id,
                        'horario_sauna'   => $horarioSauna,  // Horario del SPA
                        'horario_tinaja'  => $horarioTinaja, // Horario de tinaja
                        'horario_masaje'  => null,           // No hay masajes
                        'tipo_masaje'     => null,           // No hay masajes
                        'id_ubicacion'    => $request->input('id_ubicacion'),
                        'id_lugar_masaje' => null, // No hay masajes
                        'trago_cortesia'  => $request->input('trago_cortesia'),
                        'observacion'     => $request->input('observacion'),
                    ]);
                }

                // Caso 2: 1 SPA + 1 horario Masaje
                if ($request->has('horario_masaje') && $request->has('horario_sauna')) {
                    // Convertir horario_sauna a objeto Carbon
                    $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);
                    $horarioMasaje = Carbon::createFromFormat('H:i', $request->input('horario_masaje'));

                    // Crear una visita con solo SPA
                    $visita->update([
                        'id_reserva'      => $reserva->id,
                        'horario_sauna'   => $horarioSauna,  // Horario del SPA
                        'horario_tinaja'  => $horarioTinaja, // Horario de tinaja
                        'horario_masaje'  => $horarioMasaje, // Horario de masaje
                        'tipo_masaje'     => $request->input('tipo_masaje'),
                        'id_ubicacion'    => $request->input('id_ubicacion'),
                        'id_lugar_masaje' => $request->input('id_lugar_masaje'),
                        'trago_cortesia'  => $request->input('trago_cortesia'),
                        'observacion'     => $request->input('observacion'),
                    ]);
                }

                // Caso 3: 1 horario SPA con arreglo de masajes
                if ($request->has('masajes') && $request->has('horario_sauna')) {
                    // Obtener horario de sauna
                    $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

                    $maxPersonasPorMasaje = 2; // Máximo de masajes por horario de SPA

                    $masajesExistentes = $visitas->where('horario_sauna', $horarioSauna->format('H:i'))->take($maxPersonasPorMasaje)->values();

                    // Procesar masajes enviados en el request
                    $masajesAsignados = 0;

                    foreach ($masajes as $index => $masaje) {
                        if (isset($masajesExistentes[$masajesAsignados])) {

                            // Actualizar visita existente
                            $visita = $masajesExistentes->get($masajesAsignados);

                            $visita->update([
                                'horario_sauna'   => $horarioSauna,
                                'horario_tinaja'  => $horarioTinaja,
                                'horario_masaje'  => Carbon::createFromFormat('H:i', $masaje['horario_masaje']),
                                'tipo_masaje'     => $masaje['tipo_masaje'],
                                'id_ubicacion'    => $request->input('id_ubicacion'),
                                'id_lugar_masaje' => $masaje['id_lugar_masaje'] ?? null,
                                'trago_cortesia'  => $request->input('trago_cortesia'),
                                'observacion'     => $request->input('observacion'),
                            ]);
                        }

                        $masajesAsignados++;
                    }

                    // Eliminar visitas adicionales si hay menos masajes ahora
                    $masajesExistentes->slice($masajesAsignados)->each(function ($visita) {
                        $visita->delete();
                    });

                }

                // Caso 4: Arreglos de SPA sin masajes
                if ($request->has('spas') && ! $request->has('masajes')) {
                    dd('En proceso');
                }

                // Caso 5: Arreglos de SPAs y masajes
                if ($request->has('masajes') && $request->has('spas')) {
                    dd($masajes, $horariosSpa);
                    foreach ($horariosSpa as $indexSpa => $spa) {
                        $horarioSauna  = Carbon::createFromFormat('H:i', $spa['horario_sauna']);
                        $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

                        if (isset($visitas[$indexSpa])) {
                            // Actualizar la visita existente con horarios SPA
                            $visita = $visitas[$indexSpa];

                            $visita->update([
                                'horario_sauna'  => $horarioSauna,
                                'horario_tinaja' => $horarioTinaja,
                                'id_ubicacion'   => $request->input('id_ubicacion'),
                                'trago_cortesia' => $request->input('trago_cortesia'),
                                'observacion'    => $request->input('observacion'),
                            ]);

                            while ($contadorMasajes < $totalMasajes && $masajesAsignados < 2) {
                                $masaje = $masajes[$contadorMasajes];

                                $visita->update([
                                    'horario_masaje'  => Carbon::createFromFormat('H:i', $masaje['horario_masaje']),
                                    'tipo_masaje'     => $masaje['tipo_masaje'],
                                    'id_lugar_masaje' => $masaje['id_lugar_masaje'] ?? null,
                                ]);

                                $contadorMasajes++;
                                $masajesAsignados++;
                            }
                        } else {
                            // Crear una nueva visita si no existe
                            $visita = Visita::create([
                                'id_reserva'     => $reserva->id,
                                'horario_sauna'  => $horarioSauna,
                                'horario_tinaja' => $horarioTinaja,
                                'horario_masaje' => null,
                                'tipo_masaje'    => null,
                                'id_ubicacion'   => $request->input('id_ubicacion'),
                                'trago_cortesia' => $request->input('trago_cortesia'),
                                'observacion'    => $request->input('observacion'),
                            ]);

                            // Asignar menús a la nueva visita
                            foreach ($menusRequest as $menu) {
                                $visita->menus()->create([
                                    'id_producto_entrada'        => $menu['id_producto_entrada'],
                                    'id_producto_fondo'          => $menu['id_producto_fondo'],
                                    'id_producto_acompanamiento' => $menu['id_producto_acompanamiento'],
                                    'alergias'                   => $menu['alergias'],
                                    'observacion'                => $menu['observacion'],
                                ]);
                            }
                        }
                    }
                }

                if ($menusRequest) {
                    // Asignar menús a esta visita
                    foreach (array_values($menusRequest) as $menuData) {
                        $menu = $menus->where('id', $menuData['id'])
                            ->first();

                        if ($menu) {
                            // Actualizar menú existente
                            $menu->update([
                                'id_producto_entrada'        => $menuData['id_producto_entrada'],
                                'id_producto_fondo'          => $menuData['id_producto_fondo'],
                                'id_producto_acompanamiento' => $menuData['id_producto_acompanamiento'],
                                'alergias'                   => $menuData['alergias'],
                                'observacion'                => $menuData['observacion'],
                            ]);
                        } else {
                            // Crear nuevo menú si no existe
                            Menu::create([
                                'id_visita'                  => $idVisitaMenu,
                                'id_producto_entrada'        => $menuData['id_producto_entrada'],
                                'id_producto_fondo'          => $menuData['id_producto_fondo'],
                                'id_producto_acompanamiento' => $menuData['id_producto_acompanamiento'],
                                'alergias'                   => $menuData['alergias'],
                                'observacion'                => $menuData['observacion'],
                            ]);
                        }

                        $menuIds[] = $menu->id;
                    }

                }

                // // Si quedan masajes adicionales, crear nuevas visitas para ellos
                // while ($contadorMasajes < $totalMasajes) {
                //     $masaje = $masajes[$contadorMasajes];

                //     Visita::create([
                //         'id_reserva' => $reserva->id,
                //         'horario_sauna' => null,
                //         'horario_tinaja' => null,
                //         'horario_masaje' => Carbon::createFromFormat('H:i', $masaje['horario_masaje']),
                //         'tipo_masaje' => $masaje['tipo_masaje'],
                //         'id_ubicacion' => $request->input('id_ubicacion'),
                //         'id_lugar_masaje' => $masaje['id_lugar_masaje'] ?? null,
                //         'trago_cortesia' => $request->input('trago_cortesia'),
                //         'observacion' => $request->input('observacion'),
                //     ]);

                //     $contadorMasajes++;
                // }
            });

            Alert::success('Éxito', 'Se ha actualizado la visita')->showConfirmButton();
            session()->forget(['masajesExtra', 'almuerzosExtra']);
            return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva->id]);
        } catch (\Exception $e) {
            // Alert::error('Error', 'Ocurrió un problema al actualizar la visita. Error: ' . $e->getMessage())->showConfirmButton();
            return redirect()->back()->with('error', 'Ocurrió un problema al actualizar la visita. Error: ' . $e->getMessage())->withInput();
        }

    }

    public function destroy(Visita $visitum)
    {
        //
    }

    public function edit_ubicacion(Visita $visitum)
    {
        $fechaSeleccionada   = \Carbon\Carbon::createFromFormat('d-m-Y', $visitum->reserva->fecha_visita)->format('Y-m-d');
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

        return view('themes.backoffice.pages.visita.edit_ubicacion', [
            'visita'      => $visitum,
            'ubicaciones' => $ubicaciones,
        ]);
    }

    public function update_ubicacion(Request $request, Visita $visitum)
    {
        $ubicacionNueva = Ubicacion::where('id', '=', $request->ubicacion)
            ->first();

        $visita = Visita::findOrFail($visitum->id);
        $visita->update([
            'id_ubicacion' => $request->ubicacion,
        ]);

        Alert::success('Éxito', 'Ubicacion cambiada a ' . $ubicacionNueva->nombre)->showConfirmButton('Confirmar');
        return redirect()->route('backoffice.reserva.show', ['reserva' => $visitum->id_reserva]);
    }
}
