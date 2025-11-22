<?php
namespace App\Http\Controllers;

use App\Http\Requests\Visita\StoreRequest;
use App\Http\Requests\Visita\UpdateRequest;
use App\Mail\RegistroReservaMailable;
use App\LugarMasaje;
use App\Masaje;
use App\Menu;
use App\Producto;
use App\Reserva;
use App\Ubicacion;
use App\Visita;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $horariosDisponiblesSPA = array_diff($horarios, $horariosOcupados);

        //=================================HORAS=MASAJES=========================================

        // Horarios disponibles de 10:20 a 19:00 con intervalos de 10 minutos entre sesiones de masaje
        $horaInicioMasajes = new \DateTime('10:20');
        $horaFinMasajes    = new \DateTime('18:30');
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

        // // Filtrar horarios disponibles (ajusta si ocupas rangos completos)
        // $horariosDisponiblesMasajes = array_diff($horarios, $horariosOcupadosMasajes);

        // Obtener productos de tipo "entrada"
        $entradas = Producto::activos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'entrada');
        })->get();

        // Obtener productos de tipo "fondo"
        $fondos = Producto::activos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'fondo');
        })->get();

        // Obtener productos de tipo "postre"
        $acompañamientos = Producto::activos()->whereHas('tipoProducto', function ($query) {
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
            'cantidadMasajesExtra' => $cantidadMasajesExtra
        ]);
    }

    // public function createOLD($reserva)
    // {
    //     $masajesExtra   = session()->get('masajesExtra');
    //     $almuerzosExtra = session()->get('almuerzosExtra');

    //     $reserva              = Reserva::findOrFail($reserva);
    //     $serviciosDisponibles = $reserva->programa->servicios->pluck('nombre_servicio')->toArray();

    //     // Obtenemos la fecha seleccionada del formulario
    //     $fechaSeleccionada   = \Carbon\Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d');
    //     $ubicacionesOcupadas = DB::table('visitas')
    //         ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
    //         ->join('ubicaciones', 'visitas.id_ubicacion', '=', 'ubicaciones.id')
    //         ->where('reservas.fecha_visita', $fechaSeleccionada)
    //         ->pluck('ubicaciones.nombre')
    //         ->map(function ($nombre) {
    //             return $nombre;
    //         })
    //         ->toArray();

    //     $ubicacionesAll = DB::table('ubicaciones')
    //         ->select('id', 'nombre')
    //         ->get();

    //     $ubicaciones = $ubicacionesAll->filter(function ($ubicacion) use ($ubicacionesOcupadas) {
    //         return ! in_array($ubicacion->nombre, $ubicacionesOcupadas);
    //     })->values();

    //     // ===============================HORAS=SPA==============================================
    //     // Horarios disponibles de 10:00 a 18:30 SPA
    //     $horaInicio = new \DateTime('10:00');
    //     $horaFin    = new \DateTime('18:30');
    //     $intervalo  = new \DateInterval('PT30M');
    //     $horarios   = [];

    //     while ($horaInicio <= $horaFin) {
    //         $horarios[] = $horaInicio->format('H:i');
    //         $horaInicio->add($intervalo);
    //     }

    //     // Obtener horarios ocupados de la tabla 'visitas'
    //     $horariosOcupados = DB::table('visitas')
    //         ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
    //         ->where('reservas.fecha_visita', $fechaSeleccionada)
    //         ->pluck('visitas.horario_sauna')
    //         ->map(function ($hora) {
    //             return \Carbon\Carbon::createFromFormat('H:i:s', $hora)->format('H:i');
    //         })
    //         ->toArray();

    //     // Filtrar horarios disponibles
    //     $horariosDisponiblesSPA = array_diff($horarios, $horariosOcupados);

    //     //=================================HORAS=MASAJES=========================================

    //     // Horarios disponibles de 10:20 a 19:00 con intervalos de 10 minutos entre sesiones de masaje
    //     $horaInicioMasajes = new \DateTime('10:20');
    //     $horaFinMasajes    = new \DateTime('18:30');
    //     $duracionMasaje    = new \DateInterval('PT30M'); // 30 minutos de duración
    //     $intervalos        = new \DateInterval('PT10M'); // 10 minutos de intervalos entre sesiones
    //     $horarios          = [];

    //     while ($horaInicioMasajes <= $horaFinMasajes) {
    //         $horarios[] = $horaInicioMasajes->format('H:i');
    //         $horaInicioMasajes->add($duracionMasaje);
    //         $horaInicioMasajes->add($intervalos);
    //     }

    //     // Obtener las horas de inicio ocupadas de la tabla 'visitas' para masajes
    //     $horariosOcupadosMasajes = DB::table('masajes')
    //         ->join('reservas', 'masajes.id_reserva', '=', 'reservas.id')
    //         ->where('reservas.fecha_visita', $fechaSeleccionada)
    //         ->pluck('masajes.horario_masaje')
    //         ->filter(function ($hora) {
    //             return ! is_null($hora); // Filtra valores nulos
    //         })
    //         ->map(function ($hora) {
    //             return \Carbon\Carbon::createFromFormat('H:i:s', $hora)->format('H:i');
    //         })
    //         ->toArray();

    //     // Filtrar horarios disponibles (ajusta si ocupas rangos completos)
    //     $horariosDisponiblesMasajes = array_diff($horarios, $horariosOcupadosMasajes);

    //     // Obtener productos de tipo "entrada"
    //     $entradas = Producto::whereHas('tipoProducto', function ($query) {
    //         $query->where('nombre', 'entrada');
    //     })->get();

    //     // Obtener productos de tipo "fondo"
    //     $fondos = Producto::whereHas('tipoProducto', function ($query) {
    //         $query->where('nombre', 'fondo');
    //     })->get();

    //     // Obtener productos de tipo "postre"
    //     $acompañamientos = Producto::whereHas('tipoProducto', function ($query) {
    //         $query->where('nombre', 'acompañamiento');
    //     })->get();

    //     return view('themes.backoffice.pages.visita.create', [
    //         'reserva'         => $reserva,
    //         'ubicaciones'     => $ubicaciones,
    //         'lugares'         => LugarMasaje::all(),
    //         'servicios'       => $serviciosDisponibles,
    //         'horarios'        => $horariosDisponiblesSPA,
    //         'horasMasaje'     => $horariosDisponiblesMasajes,
    //         'entradas'        => $entradas,
    //         'fondos'          => $fondos,
    //         'acompañamientos' => $acompañamientos,
    //         'masajesExtra'    => $masajesExtra,
    //         'almuerzosExtra'  => $almuerzosExtra,
    //     ]);
    // }

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
        $masajesExtra   = session()->get('masajesExtra');

        $almuerzoIncluido = $programa->servicios->pluck('nombre_servicio')->toArray();

        try {
            DB::transaction(function () use ($request, &$reserva, &$visita, &$cliente, $almuerzoIncluido, $almuerzosExtra, $masajesExtra, $personas) {

                $cliente = $reserva->cliente;

                // Caso 1: Solo SPA (sin masajes)
                if (! $request->has('masajes') && ! $request->has('horario_masaje') && $request->has('horario_sauna')) {
                    // Convertir horario_sauna a objeto Carbon
                    $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

                    // Crear una visita con solo SPA
                    $visita = Visita::create([
                        'id_reserva'     => $reserva->id,
                        'horario_sauna'  => $horarioSauna,  // Horario del SPA
                        'horario_tinaja' => $horarioTinaja, // Horario de tinaja
                        'id_ubicacion'   => $request->input('id_ubicacion'),
                        'trago_cortesia' => $request->input('trago_cortesia'),
                        'observacion'    => $request->input('observacion'),
                    ]);

                    session()->forget(['masajesExtra', 'almuerzosExtra', 'cantidadMasajesExtra']);
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
                        Masaje::create([
                            'horario_masaje'  => $horarioMasaje, // Horario de masaje
                            'tipo_masaje'     => $request->input('tipo_masaje'),
                            'id_lugar_masaje' => $request->input('id_lugar_masaje'),
                            'persona'         => $i,
                            'id_reserva'      => $reserva->id,
                        ]);
                    }

                    session()->forget(['masajesExtra', 'almuerzosExtra','cantidadMasajesExtra']);

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
                                'tipo_masaje'     => $horario['tipo_masaje'] ?? 'Relajación',
                                'id_lugar_masaje' => $horario['id_lugar_masaje'] ?? 1,
                                'persona'         => $contadorPersonas,
                                'id_reserva'       => $reserva->id,
                            ]);
                            $contadorPersonas++;

                        }
                    }

                    session()->forget(['masajesExtra', 'almuerzosExtra', 'cantidadMasajesExtra']);
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

                    session()->forget(['masajesExtra', 'almuerzosExtra', 'cantidadMasajesExtra']);
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
                                'tipo_masaje'     => $horario['tipo_masaje'] ?? 'Relajación',
                                'id_lugar_masaje' => $horario['id_lugar_masaje'] ?? 1,
                                'persona'         => $contadorPersonas,
                                'id_reserva'       => $reserva->id,
                            ]);
                            $contadorPersonas++;

                        }
                    }

                    session()->forget(['masajesExtra', 'almuerzosExtra', 'cantidadMasajesExtra']);
                }

                // En caso de no registrar horarios
                $arrayMasajes  = $request->input('masajes', []);
                $arraySpas     = $request->input('spas', []);
                $incluyeMasaje = $reserva->programa->servicios->contains('nombre_servicio', 'Masaje') || $masajesExtra;

                // Validar que en los arreglos internos de `masajes` exista al menos una clave `horario_masaje`.
                $tieneHorarioMasaje = ! empty(array_filter($arrayMasajes, function ($item) {
                    return is_array($item) && array_key_exists('horario_masaje', $item);
                }));

                // Caso 6: Sin data
                if ((empty($arrayMasajes) || !$tieneHorarioMasaje) && empty($request->input('horario_masaje')) && empty($arraySpas) && empty($request->input('horario_sauna'))) {

                    $cantidadPersonas     = $reserva->cantidad_personas;
                    $maxPersonasPorVisita = 5;
                    $visita               = null;

                    for ($i = 1; $i <= ceil($cantidadPersonas / $maxPersonasPorVisita); $i++) {
                        $visita = Visita::create([
                            'horario_sauna'  => null,
                            'horario_tinaja' => null,
                            'trago_cortesia' => $request->input('trago_cortesia') ?? null,
                            'observacion'    => null,
                            'id_reserva'     => $reserva->id,
                            'id_ubicacion'   => $request->input('id_ubicacion') ?? null,
                        ]);
                    }

                    if ($incluyeMasaje) {
                        for ($i = 1; $i <= $personas; $i++) {
                            Masaje::create([
                                'horario_masaje'  => null,
                                'tipo_masaje'     => null,
                                'id_lugar_masaje' => 1,
                                'persona'         => $i,
                                'id_reserva'      => $reserva->id,
                                'user_id'         => null,
                            ]);
                        }
                    }
                }

                // Menus
                if (in_array('Almuerzo', $almuerzoIncluido) || $almuerzosExtra) {
                    $menusExistentes = Menu::where('id_reserva', $reserva->id)->count();
                    if($menusExistentes === 0){
                        $menusPayload =  $request->input('menus', []);

                        if (!is_array($menusPayload)) {
                            $menusPayload = [];
                        }

                        //Si vienen menus en el Payload crear
                        foreach ($menusPayload as $menu) {
                            if (!is_array($menu)) continue;

                            Menu::create([
                                'id_reserva'                 => $reserva->id,
                                'id_producto_entrada'        => $menu['id_producto_entrada'] ?? null,
                                'id_producto_fondo'          => $menu['id_producto_fondo'] ?? null,
                                'id_producto_acompanamiento' => $menu['id_producto_acompanamiento'] ?? null,
                                'alergias'                   => $menu['alergias'] ?? null,
                                'observacion'                => $menu['observacion'] ?? null,
                            ]);
                        }


                        //Si no vienen, crear menus vacios
                        $cantidadNecesaria = (int) $personas;
                        $creados = max(0, count($menusPayload));
                        $faltantes = max(0, $cantidadNecesaria - $creados);

                        for ($i=0; $i < $faltantes; $i++) { 
                            Menu::create([
                                'id_reserva'                 => $reserva->id,
                                'id_producto_entrada'        => null,
                                'id_producto_fondo'          => null,
                                'id_producto_acompanamiento' => null,
                                'alergias'                   => null,
                                'observacion'                => null,
                            ]);
                        }
                    }


                    // //Anterior controlador de los menus
                    // foreach ($request->menus as $menu) {
                    //     Menu::create([
                    //         'id_reserva'                 => $reserva->id,
                    //         'id_producto_entrada'        => $menu['id_producto_entrada'] ?? null,
                    //         'id_producto_fondo'          => $menu['id_producto_fondo'] ?? null,
                    //         'id_producto_acompanamiento' => $menu['id_producto_acompanamiento'] ?? null,
                    //         'alergias'                   => $menu['alergias'] ?? null,
                    //         'observacion'                => $menu['observacion'] ?? null,
                    //     ]);
                    // }




                }

            });

            if ($cliente && $visita) {
                Mail::to($cliente->correo)->send(new RegistroReservaMailable($visita, $reserva, $cliente, $programa));
            }

            return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva])->with('success', 'Se ha generado la visita.');;

        } catch (\Exception $e) {

            Log::error('Error al generar visita en store(): ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'mensaje' => 'Fallo la creacion de la visita, revisa el menu o los servicios',
                'reserva_id' => $reserva->id ?? null,
                'request_data' => $request->all()
            ]);

            return redirect()->back()->withInput()->with('error', 'Debe completar todo el formulario o NO seleccionar nada');
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
        // $bytes = 1048580000;
        $bytes = $visitum->id;
        dd($this->formatBytes($bytes));
    }

    public function edit(Reserva $reserva, Visita $visita)
    {
        $masajesExtra         = session()->get('masajesExtra');
        $almuerzosExtra       = session()->get('almuerzosExtra');
        $cantidadMasajesExtra = session()->get('cantidadMasajesExtra');

        $serviciosDisponibles = $reserva->programa->servicios->pluck('nombre_servicio')->toArray();
        // $menus = $reserva->visitas->last()->menus;

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
            return !in_array($ubicacion->nombre, $ubicacionesOcupadas);
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

        // // Filtrar horarios disponibles (ajusta si ocupas rangos completos)
        // $horariosDisponiblesMasajes = array_diff($horarios, $horariosOcupadosMasajes);

        // Obtener productos de tipo "entrada"
        $entradas = Producto::validos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'entrada');
        })->get();

        // Obtener productos de tipo "fondo"
        $fondos = Producto::validos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'fondo');
        })->get();

        // Obtener productos de tipo "postre"
        $acompañamientos = Producto::validos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'acompañamiento');
        })->get();

        
        

        // Obtener la última visita de la reserva
        $ultimaVisita = $reserva->visitas->last();

        // Obtener la cantidad de personas en la reserva
        $cantidadPersonas = $reserva->cantidad_personas;

        // Obtener los menús de la última visita
        $menus = isset($reserva->menus) ? $reserva->menus : collect([]);

        // Si la cantidad de menús es menor a la cantidad de personas en la reserva, agregamos menús vacíos
        $menusFaltantes = $cantidadPersonas - $menus->count();
        for ($i = 0; $i < $menusFaltantes; $i++) {
            $menus->push(new Menu()); // Agregar menú vacío
        }


        return view('themes.backoffice.pages.visita.edit', [
            'visita'          => $visita,
            'visitas'         => $reserva->visitas,
            'masajes'         => $reserva->masajes,
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

    // public function OLDupdate(UpdateRequest $request, Reserva $reserva, Visita $visita)
    // {
    //     dd($request);
    //     $visitas      = $reserva->visitas;
    //     $menus        = $reserva->menus;
    //     $idVisitaMenu = $visitas->last()->id;
    //     $menuIds      = [];

    //     try {
    //         DB::transaction(function () use ($request, &$reserva, &$visitas, &$menus, &$menuIds, $idVisitaMenu, &$visita) {
    //             $horariosSpa = $request->input('spas'); // Obtener los horarios SPA del request
    //             if ($request->has('masajes')) {
    //                 $masajes      = $request->input('masajes'); // Obtener los horarios de masajes del request
    //                 $totalMasajes = count($masajes);
    //             }

    //             if ($request->has('menus')) {
    //                 $menusRequest = $request->input('menus'); // Obtener los menús del request
    //             }

    //             $contadorMasajes = 0;

    //             // dd($request);
    //             // Caso 1: Solo SPA (sin masajes)
    //             if (! $request->has('masajes') && ! $request->has('horario_masaje') && $request->has('horario_sauna')) {
    //                 // Convertir horario_sauna a objeto Carbon
    //                 $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
    //                 $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

    //                 // Crear una visita con solo SPA
    //                 $visita->update([
    //                     'id_reserva'      => $reserva->id,
    //                     'horario_sauna'   => $horarioSauna,  // Horario del SPA
    //                     'horario_tinaja'  => $horarioTinaja, // Horario de tinaja
    //                     'horario_masaje'  => null,           // No hay masajes
    //                     'tipo_masaje'     => null,           // No hay masajes
    //                     'id_ubicacion'    => $request->input('id_ubicacion'),
    //                     'id_lugar_masaje' => null, // No hay masajes
    //                     'trago_cortesia'  => $request->input('trago_cortesia'),
    //                     'observacion'     => $request->input('observacion'),
    //                 ]);
    //             }

    //             // Caso 2: 1 SPA + 1 horario Masaje
    //             if ($request->has('horario_masaje') && $request->has('horario_sauna')) {
    //                 // Convertir horario_sauna a objeto Carbon
    //                 $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
    //                 $horarioTinaja = $horarioSauna->copy()->addMinutes(15);
    //                 $horarioMasaje = Carbon::createFromFormat('H:i', $request->input('horario_masaje'));

    //                 // Crear una visita con solo SPA
    //                 $visita->update([
    //                     'id_reserva'      => $reserva->id,
    //                     'horario_sauna'   => $horarioSauna,  // Horario del SPA
    //                     'horario_tinaja'  => $horarioTinaja, // Horario de tinaja
    //                     'horario_masaje'  => $horarioMasaje, // Horario de masaje
    //                     'tipo_masaje'     => $request->input('tipo_masaje'),
    //                     'id_ubicacion'    => $request->input('id_ubicacion'),
    //                     'id_lugar_masaje' => $request->input('id_lugar_masaje'),
    //                     'trago_cortesia'  => $request->input('trago_cortesia'),
    //                     'observacion'     => $request->input('observacion'),
    //                 ]);
    //             }

    //             // Caso 3: 1 horario SPA con arreglo de masajes
    //             if ($request->has('masajes') && $request->has('horario_sauna')) {
    //                 // Obtener horario de sauna
    //                 $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
    //                 $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

    //                 $maxPersonasPorMasaje = 2; // Máximo de masajes por horario de SPA

    //                 $masajesExistentes = $visitas->where('horario_sauna', $horarioSauna->format('H:i'))->take($maxPersonasPorMasaje)->values();

    //                 // Procesar masajes enviados en el request
    //                 $masajesAsignados = 0;

    //                 foreach ($masajes as $index => $masaje) {
    //                     if (isset($masajesExistentes[$masajesAsignados])) {

    //                         // Actualizar visita existente
    //                         $visita = $masajesExistentes->get($masajesAsignados);

    //                         $visita->update([
    //                             'horario_sauna'   => $horarioSauna,
    //                             'horario_tinaja'  => $horarioTinaja,
    //                             'horario_masaje'  => Carbon::createFromFormat('H:i', $masaje['horario_masaje']),
    //                             'tipo_masaje'     => $masaje['tipo_masaje'],
    //                             'id_ubicacion'    => $request->input('id_ubicacion'),
    //                             'id_lugar_masaje' => $masaje['id_lugar_masaje'] ?? null,
    //                             'trago_cortesia'  => $request->input('trago_cortesia'),
    //                             'observacion'     => $request->input('observacion'),
    //                         ]);
    //                     }

    //                     $masajesAsignados++;
    //                 }

    //                 // Eliminar visitas adicionales si hay menos masajes ahora
    //                 $masajesExistentes->slice($masajesAsignados)->each(function ($visita) {
    //                     $visita->delete();
    //                 });

    //             }

    //             // Caso 4: Arreglos de SPA sin masajes
    //             if ($request->has('spas') && ! $request->has('masajes')) {
    //                 dd('En proceso');
    //             }

    //             // Caso 5: Arreglos de SPAs y masajes
    //             if ($request->has('masajes') && $request->has('spas')) {
    //                 dd($masajes, $horariosSpa);
    //                 foreach ($horariosSpa as $indexSpa => $spa) {
    //                     $horarioSauna  = Carbon::createFromFormat('H:i', $spa['horario_sauna']);
    //                     $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

    //                     if (isset($visitas[$indexSpa])) {
    //                         // Actualizar la visita existente con horarios SPA
    //                         $visita = $visitas[$indexSpa];

    //                         $visita->update([
    //                             'horario_sauna'  => $horarioSauna,
    //                             'horario_tinaja' => $horarioTinaja,
    //                             'id_ubicacion'   => $request->input('id_ubicacion'),
    //                             'trago_cortesia' => $request->input('trago_cortesia'),
    //                             'observacion'    => $request->input('observacion'),
    //                         ]);

    //                         while ($contadorMasajes < $totalMasajes && $masajesAsignados < 2) {
    //                             $masaje = $masajes[$contadorMasajes];

    //                             $visita->update([
    //                                 'horario_masaje'  => Carbon::createFromFormat('H:i', $masaje['horario_masaje']),
    //                                 'tipo_masaje'     => $masaje['tipo_masaje'],
    //                                 'id_lugar_masaje' => $masaje['id_lugar_masaje'] ?? null,
    //                             ]);

    //                             $contadorMasajes++;
    //                             $masajesAsignados++;
    //                         }
    //                     } else {
    //                         // Crear una nueva visita si no existe
    //                         $visita = Visita::create([
    //                             'id_reserva'     => $reserva->id,
    //                             'horario_sauna'  => $horarioSauna,
    //                             'horario_tinaja' => $horarioTinaja,
    //                             'horario_masaje' => null,
    //                             'tipo_masaje'    => null,
    //                             'id_ubicacion'   => $request->input('id_ubicacion'),
    //                             'trago_cortesia' => $request->input('trago_cortesia'),
    //                             'observacion'    => $request->input('observacion'),
    //                         ]);

    //                         // Asignar menús a la nueva visita
    //                         foreach ($menusRequest as $menu) {
    //                             $reserva->menus()->create([
    //                                 'id_producto_entrada'        => $menu['id_producto_entrada'],
    //                                 'id_producto_fondo'          => $menu['id_producto_fondo'],
    //                                 'id_producto_acompanamiento' => $menu['id_producto_acompanamiento'],
    //                                 'alergias'                   => $menu['alergias'],
    //                                 'observacion'                => $menu['observacion'],
    //                             ]);
    //                         }
    //                     }
    //                 }
    //             }

    //             if ($menusRequest) {
    //                 // Asignar menús a esta visita
    //                 foreach (array_values($menusRequest) as $menuData) {
    //                     $menu = $menus->where('id', $menuData['id'])
    //                         ->first();

    //                     if ($menu) {
    //                         // Actualizar menú existente
    //                         $menu->update([
    //                             'id_producto_entrada'        => $menuData['id_producto_entrada'],
    //                             'id_producto_fondo'          => $menuData['id_producto_fondo'],
    //                             'id_producto_acompanamiento' => $menuData['id_producto_acompanamiento'],
    //                             'alergias'                   => $menuData['alergias'],
    //                             'observacion'                => $menuData['observacion'],
    //                         ]);
    //                     } else {
    //                         // Crear nuevo menú si no existe
    //                         Menu::create([
    //                             'id_visita'                  => $idVisitaMenu,
    //                             'id_producto_entrada'        => $menuData['id_producto_entrada'],
    //                             'id_producto_fondo'          => $menuData['id_producto_fondo'],
    //                             'id_producto_acompanamiento' => $menuData['id_producto_acompanamiento'],
    //                             'alergias'                   => $menuData['alergias'],
    //                             'observacion'                => $menuData['observacion'],
    //                         ]);
    //                     }

    //                     $menuIds[] = $menu->id;
    //                 }

    //             }

    //             // // Si quedan masajes adicionales, crear nuevas visitas para ellos
    //             // while ($contadorMasajes < $totalMasajes) {
    //             //     $masaje = $masajes[$contadorMasajes];

    //             //     Visita::create([
    //             //         'id_reserva' => $reserva->id,
    //             //         'horario_sauna' => null,
    //             //         'horario_tinaja' => null,
    //             //         'horario_masaje' => Carbon::createFromFormat('H:i', $masaje['horario_masaje']),
    //             //         'tipo_masaje' => $masaje['tipo_masaje'],
    //             //         'id_ubicacion' => $request->input('id_ubicacion'),
    //             //         'id_lugar_masaje' => $masaje['id_lugar_masaje'] ?? null,
    //             //         'trago_cortesia' => $request->input('trago_cortesia'),
    //             //         'observacion' => $request->input('observacion'),
    //             //     ]);

    //             //     $contadorMasajes++;
    //             // }
    //         });

    //         Alert::success('Éxito', 'Se ha actualizado la visita')->showConfirmButton();
    //         session()->forget(['masajesExtra', 'almuerzosExtra']);
    //         return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva->id]);
    //     } catch (\Exception $e) {
    //         // Alert::error('Error', 'Ocurrió un problema al actualizar la visita. Error: ' . $e->getMessage())->showConfirmButton();
    //         return redirect()->back()->with('error', 'Ocurrió un problema al actualizar la visita. Error: ' . $e->getMessage())->withInput();
    //     }

    // }

    public function update(UpdateRequest $request, Reserva $reserva)
    {
        // Obtener cantidad de personas
        $personas = session()->get('cantidadMasajesExtra')
            ?? $reserva->cantidad_masajes
            ?? $reserva->cantidad_personas;

        $programa       = $reserva->programa;
        $almuerzosExtra = session()->get('almuerzosExtra');
        $masajesExtra   = session()->get('masajesExtra');
        $almuerzoIncluido = $programa->servicios->pluck('nombre_servicio')->toArray();

        try {

            DB::transaction(function () use ($request, &$reserva, $almuerzoIncluido, $almuerzosExtra, $masajesExtra, $personas, $programa) {
                // Eliminar registros anteriores relacionados
                $reserva->visitas()->delete();
                $reserva->masajes()->delete();
                $reserva->menus()->delete();

                $cliente = $reserva->cliente;

                // Caso 1: Solo SPA (sin masajes)
                if (! $request->has('masajes') && ! $request->has('horario_masaje') && $request->has('horario_sauna')) {
                    
                    $visita = $this->soloSpa($request, $reserva);
                }

                // Caso 2: 1 SPA + 1 horario Masaje
                if ($request->has('horario_masaje') && $request->has('horario_sauna')) {
                    
                    $visita = $this->spaConMasaje($request, $reserva, $personas);

                }

                // Caso 3: 1 horario SPA con arreglo de masajes
                if ($request->has('masajes') && $request->has('horario_sauna')) {
                    
                    $visita = $this->spaConMasajes($request, $reserva,$personas);
                }

                // Caso 4: Arreglos de SPA sin masajes
                if (! $request->has('masajes') && $request->has('spas')) {
                    
                    $visita = $this->spaSinMasajes($request, $reserva);
                }

                // Caso 5: Arreglos de SPA y masajes
                if ($request->has('masajes') && $request->has('spas')) {
                    
                    $visita = $this->spasConMasajes($request, $reserva, $personas);
                }

                // En caso de no registrar horarios
                $arrayMasajes  = $request->input('masajes', []);
                $arraySpas     = $request->input('spas', []);
                $incluyeMasaje = $reserva->programa->servicios->contains('nombre_servicio', 'Masaje') || $masajesExtra;

                // Validar que en los arreglos internos de `masajes` exista al menos una clave `horario_masaje`.
                $tieneHorarioMasaje = ! empty(array_filter($arrayMasajes, function ($item) {
                    return is_array($item) && array_key_exists('horario_masaje', $item);
                }));

                // Caso 6: Sin data
                if ((empty($arrayMasajes) || !$tieneHorarioMasaje) && empty($request->input('horario_masaje')) && empty($arraySpas) && empty($request->input('horario_sauna'))) {

                    $this->sinData($request, $reserva, $incluyeMasaje, $personas);
                }

                // Menus
                if (in_array('Almuerzo', $almuerzoIncluido) || $almuerzosExtra) {

                    foreach ($request->menus as $menu) {
                        Menu::create([
                            'id_reserva'                 => $reserva->id,
                            'id_producto_entrada'        => $menu['id_producto_entrada'] ?? null,
                            'id_producto_fondo'          => $menu['id_producto_fondo'] ?? null,
                            'id_producto_acompanamiento' => $menu['id_producto_acompanamiento'] ?? null,
                            'alergias'                   => $menu['alergias'] ?? null,
                            'observacion'                => $menu['observacion'] ?? null,
                        ]);
                    }
                }

                // if ($cliente && $visita) {
                //     Mail::to($cliente->correo)->send(new RegistroReservaMailable($visita, $reserva, $cliente, $programa));
                // }
            });

        

            Alert::success('Actualizado', 'La visita ha sido modificada correctamente')->showConfirmButton();
            return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva]);

        } catch (\Exception $e) {
            Alert::error('Error', 'Debe completar todo el formulario o NO seleccionar nada')->showConfirmButton();
            return redirect()->back()->withInput();
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
        $reserva = $visitum->reserva;
        $visitas = $reserva->visitas;
        foreach ($visitas as $visita) {
            $visita->update([
                'id_ubicacion' => $request->ubicacion,
            ]);
        }

        Alert::success('Éxito', 'Ubicacion cambiada a ' . $ubicacionNueva->nombre)->showConfirmButton('Confirmar');
        return redirect()->route('backoffice.reserva.show', ['reserva' => $visitum->id_reserva]);
    }

    public function register(Reserva $reserva, Visita $visita)
    {
        session()->get('masajesExtra') ? $masajesExtra                 = session()->get('masajesExtra') : $masajesExtra                 = null;
        session()->get('almuerzosExtra') ? $almuerzosExtra             = session()->get('almuerzosExtra') : $almuerzosExtra             = null;
        session()->get('cantidadMasajesExtra') ? $cantidadMasajesExtra = session()->get('cantidadMasajesExtra') : $cantidadMasajesExtra = null;

        $serviciosDisponibles = $reserva->programa->servicios->pluck('nombre_servicio')->toArray();
        // Obtenemos la fecha seleccionada del formulario
        $fechaSeleccionada = \Carbon\Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d');

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
        $entradas = Producto::validos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'entrada');
        })->get();

        // Obtener productos de tipo "fondo"
        $fondos = Producto::validos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'fondo');
        })->get();

        // Obtener productos de tipo "postre"
        $acompañamientos = Producto::validos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'acompañamiento');
        })->get();

        return view('themes.backoffice.pages.visita.register', [
            'visita'          => $visita,
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

    public function register_update(Request $request, Reserva $reserva, Visita $visita)
    {

        $menusActuales   = Menu::where('id_visita', $reserva->visitas->last()->id)->get()->keyBy('id');
        $visitasActuales = Visita::where('id_reserva', $reserva->id)->get()->keyBy('id');
        $masajesActuales = Masaje::where('id_visita', $reserva->visitas->last()->id)->get()->keyBy('id');

        // dd($visitasActuales, $visita);

        if (session()->get('cantidadMasajesExtra') !== null) {
            $personas = session()->get('cantidadMasajesExtra');
        } elseif ($reserva->cantidad_masajes !== null) {
            $personas = $reserva->cantidad_masajes;
        } else {
            $personas = $reserva->cantidad_personas;
        }

        $cliente        = null;
        $programa       = $reserva->programa;
        $almuerzosExtra = session()->get('almuerzosExtra');
        $masajesExtra   = session()->get('masajesExtra');

        $almuerzoIncluido = $programa->servicios->pluck('nombre_servicio')->toArray();

        try {
            DB::transaction(function () use ($request, &$reserva, &$visita, &$cliente, $almuerzoIncluido, $almuerzosExtra, $personas, &$menusActuales, &$visitasActuales, &$masajesActuales, $masajesExtra) {

                $cliente = $reserva->cliente;

                // Caso 1: Solo SPA (sin masajes)
                if (! $request->has('masajes') && ! $request->has('horario_masaje') && $request->has('horario_sauna')) {
                    // Convertir horario_sauna a objeto Carbon
                    $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

                    // Crear una visita con solo SPA
                    $visita->update([
                        'id_reserva'     => $reserva->id,
                        'horario_sauna'  => $horarioSauna,
                        'horario_tinaja' => $horarioTinaja,
                        'id_ubicacion'   => $request->input('id_ubicacion'),
                        'trago_cortesia' => $request->input('trago_cortesia'),
                        'observacion'    => $request->input('observacion'),
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
                        'id_reserva'     => $reserva->id,
                        'horario_sauna'  => $horarioSauna,
                        'horario_tinaja' => $horarioTinaja,
                        'id_ubicacion'   => $request->input('id_ubicacion'),
                        'trago_cortesia' => $request->input('trago_cortesia'),
                        'observacion'    => $request->input('observacion'),
                    ]);


                    $masajes        = Masaje::where('id_visita', $visita->id)->get();

                    foreach ($masajes as $masaje) {
                        
                        $masaje->update([
                            'horario_masaje'  => $horarioMasaje,
                            'tipo_masaje'     => $request->input('tipo_masaje'),
                            'id_lugar_masaje' => $request->input('id_lugar_masaje'),
                        ]);
                    }

                }

                // Caso 3: 1 horario SPA con arreglo de masajes
                if ($request->has('masajes') && $request->has('horario_sauna')) {
                    // Obtener horario de sauna
                    $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

                    // Inicializar variables
                    $masajes               = array_values($request->input('masajes'));
                    $masajesActuales = $masajesActuales->values();
                    $contadorPersonas      = 1; // Contador de personas que reciben masaje
                    $maxPersonasPorHorario = 2; // Máximo de personas por cada horario de masaje
                    $totalMasajes          = $personas;

                    // Actualizar el SPA
                    $visita->update([
                        'id_reserva'     => $reserva->id,
                        'horario_sauna'  => $horarioSauna,
                        'horario_tinaja' => $horarioTinaja,
                        'id_ubicacion'   => $request->input('id_ubicacion'),
                        'trago_cortesia' => $request->input('trago_cortesia'),
                        'observacion'    => $request->input('observacion'),
                    ]);

                    // Procesar los masajes (Pendiente)
                    $indiceMasajeActual = 0; // Para recorrer los registros en la base de datos

                    foreach ($masajes as $index => $masajeData) {
                        for ($i = 1; $i <= 2; $i++) { // Crear dos registros por cada "Par" en el formulario
                            if (! isset($masajesActuales[$indiceMasajeActual])) {
                                break; // Evitar errores de índice si hay menos registros de los esperados
                            }

                            $masaje = $masajesActuales[$indiceMasajeActual];

                            try {
                                $horarioMasaje = Carbon::createFromFormat('H:i', $masajeData['horario_masaje']);
                            } catch (\Exception $e) {
                                continue; // Saltar en caso de error
                            }

                            // Actualizar el registro de masaje
                            $masaje->update([
                                'horario_masaje'  => $horarioMasaje,
                                'tipo_masaje'     => $masajeData['tipo_masaje'],
                                'id_lugar_masaje' => $masajeData['id_lugar_masaje'] ?? null,
                            ]);

                            $indiceMasajeActual++;
                        }
                    }
                }

                // Caso 4: Arreglos de SPA sin masajes
                if (! $request->has('masajes') && $request->has('spas')) {
                    $spas            = array_values($request->input('spas'));
                    $visitasActuales = $visitasActuales->values();

                    foreach ($visitasActuales as $index => $visita) {
                        // Asegúrate de que el índice `$index` existe en `$spas`
                        if (isset($spas[$index])) {
                            $spa = $spas[$index];

                            // Validar que el horario_sauna exista en los datos del request
                            if (isset($spa['horario_sauna'])) {
                                try {
                                    $horarioSauna  = Carbon::createFromFormat('H:i', $spa['horario_sauna']);
                                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);
                                } catch (\Exception $e) {
                                    // Manejar error de formato si es necesario
                                    continue;
                                }

                                // Actualizar la visita correspondiente
                                $visita->update([
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
                }

                // Caso 5: Arreglos de SPA y masajes
                if ($request->has('masajes') && $request->has('spas')) {
                    // Inicializar variables
                    $spas                  = array_values($request->input('spas'));
                    $masajes               = array_values($request->input('masajes'));
                    $contadorPersonas      = 1;
                    $maxPersonasPorHorario = 2;
                    $totalMasajes          = $personas;

                    $visitasActuales = $visitasActuales->values();
                    $masajesActuales = $masajesActuales->values();

                    foreach ($visitasActuales as $index => $visita) {
                        // Asegúrate de que el índice `$index` existe en `$spas`
                        if (isset($spas[$index])) {
                            $spa = $spas[$index];

                            // Validar que el horario_sauna exista en los datos del request
                            if (isset($spa['horario_sauna'])) {
                                try {
                                    $horarioSauna  = Carbon::createFromFormat('H:i', $spa['horario_sauna']);
                                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);
                                } catch (\Exception $e) {
                                    // Manejar error de formato si es necesario
                                    continue;
                                }

                                // Actualizar la visita correspondiente
                                $visita->update([
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

                    $indiceMasajeActual = 0; // Para recorrer los registros en la base de datos

                    foreach ($masajes as $index => $masajeData) {
                        for ($i = 1; $i <= 2; $i++) { // Crear dos registros por cada "Par" en el formulario
                            if (! isset($masajesActuales[$indiceMasajeActual])) {
                                break; // Evitar errores de índice si hay menos registros de los esperados
                            }

                            $masaje = $masajesActuales[$indiceMasajeActual];

                            try {
                                $horarioMasaje = Carbon::createFromFormat('H:i', $masajeData['horario_masaje']);
                            } catch (\Exception $e) {
                                continue; // Saltar en caso de error
                            }

                            // Actualizar el registro de masaje
                            $masaje->update([
                                'horario_masaje'  => $horarioMasaje,
                                'tipo_masaje'     => $masajeData['tipo_masaje'],
                                'id_lugar_masaje' => $masajeData['id_lugar_masaje'] ?? null,
                            ]);

                            $indiceMasajeActual++; // Avanzar en los registros de la base de datos
                        }
                    }

                }

                // Menus
                if (in_array('Almuerzo', $almuerzoIncluido) || $almuerzosExtra) {

                    $menusActuales = $menusActuales->values();
                    $menus         = array_values($request->menus);

                    foreach ($menusActuales as $index => $menu) {
                        if (isset($menus[$index])) {
                            $menuData = $menus[$index];

                            if (! isset($menuData['id_producto_entrada']) && ! isset($menuData['id_producto_fondo'])) {
                                continue;
                            }

                            $menu->update([
                                'id_producto_entrada'        => $menuData['id_producto_entrada'] ?? null,
                                'id_producto_fondo'          => $menuData['id_producto_fondo'] ?? null,
                                'id_producto_acompanamiento' => $menuData['id_producto_acompanamiento'] ?? null,
                                'alergias'                   => $menuData['alergias'] ?? null,
                                'observacion'                => $menuData['observacion'] ?? null,
                            ]);

                        } else {
                            Alert::toast('Surgió un problema con los menús', 'error')->toToast('center');
                            return redirect()->back();
                        }
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
            Alert::error('Error', 'Algo salio mal, intente nuevamente. ' . $e)->showConfirmButton();
            return redirect()->back()->withInput();
        }
    }

    public function menu(Reserva $reserva, Visita $visita)
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
                $entradas = Producto::validos()->whereHas('tipoProducto', function ($query) {
                    $query->where('nombre', 'entrada');
                })->get();
        
                // Obtener productos de tipo "fondo"
                $fondos = Producto::validos()->whereHas('tipoProducto', function ($query) {
                    $query->where('nombre', 'fondo');
                })->get();
        
                // Obtener productos de tipo "postre"
                $acompañamientos = Producto::validos()->whereHas('tipoProducto', function ($query) {
                    $query->where('nombre', 'acompañamiento');
                })->get();

        return view('themes.backoffice.pages.visita.menu.edit', [
            'reserva'           => $reserva, 
            'visita'            => $visita,
            'servicios'         => $servicios,
            'menus'             => $menus,
            'entradas'          => $entradas,
            'fondos'            => $fondos,
            'acompañamientos'   => $acompañamientos,
            'almuerzosExtra'    => $almuerzosExtra,
        ]);
    }

    public function menu_update(Request $request, Reserva $reserva, Visita $visita)
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



    public function spa(Reserva $reserva, Visita $visita)
    {
        $spas = $reserva->visitas;

        $fechaSeleccionada   = \Carbon\Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d');

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
        $horariosDisponiblesSPA = array_diff($horarios, $horariosOcupados);

        return view('themes.backoffice.pages.visita.spa.edit', [
            'reserva' => $reserva,
            'visita' => $visita,
            'spas' => $spas,
            'horarios' => $horariosDisponiblesSPA,
        ]);
    }

    public function spa_update(Request $request, Reserva $reserva)
    {
        $request->validate([
            'spas.*.horario_sauna' => 'required|string',
            'spas.*.observacion' => 'nullable|string',
            'trago_cortesia' => 'required|string',
        ]);


        try {
            foreach ($request->spas as $id => $spa) {
                $horaSpa = Carbon::createFromFormat('H:i', $spa['horario_sauna']);
                $horaTinaja = $horaSpa->copy()->addMinutes(15);
                
                $visita = Visita::findOrFail($id);

                $visita->update([
                    'horario_sauna' => $horaSpa,
                    'horario_tinaja' => $horaTinaja,
                    'trago_cortesia' => $request->input('trago_cortesia'),
                    'observacion' => $spa['observacion']
                ]);
            }

            return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva])->with('success', 'Horarios SPA actualizados correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ocurrió un error al actualizar los horarios de SPA.');
        }

    }


    private function soloSpa(Request $request, Reserva $reserva):void{
        // Convertir horario_sauna a objeto Carbon
        $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
        $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

        // Crear una visita con solo SPA
        Visita::create([
            'id_reserva'     => $reserva->id,
            'horario_sauna'  => $horarioSauna,  // Horario del SPA
            'horario_tinaja' => $horarioTinaja, // Horario de tinaja
            'id_ubicacion'   => $request->input('id_ubicacion'),
            'trago_cortesia' => $request->input('trago_cortesia'),
            'observacion'    => $request->input('observacion'),
        ]);

        session()->forget(['masajesExtra', 'almuerzosExtra', 'cantidadMasajesExtra']);

    }

    private function spaConMasaje(Request $request, Reserva $reserva, $personas):void
    {
        // Convertir horario_sauna a objeto Carbon
        $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
        $horarioTinaja = $horarioSauna->copy()->addMinutes(15);
        $horarioMasaje = Carbon::createFromFormat('H:i', $request->input('horario_masaje'));

        // Crear una visita con solo SPA
        Visita::create([
            'id_reserva'     => $reserva->id,
            'horario_sauna'  => $horarioSauna,  // Horario del SPA
            'horario_tinaja' => $horarioTinaja, // Horario de tinaja
            'id_ubicacion'   => $request->input('id_ubicacion'),
            'trago_cortesia' => $request->input('trago_cortesia'),
            'observacion'    => $request->input('observacion'),
        ]);

        for ($i = 1; $i <= $personas; $i++) {
            Masaje::create([
                'horario_masaje'  => $horarioMasaje, // Horario de masaje
                'tipo_masaje'     => $request->input('tipo_masaje'),
                'id_lugar_masaje' => $request->input('id_lugar_masaje') ?? 1,
                'persona'         => $i,
                'id_reserva'      => $reserva->id,
            ]);
        }

        session()->forget(['masajesExtra', 'almuerzosExtra','cantidadMasajesExtra']);
    }

    private function spaConMasajes(Request $request, Reserva $reserva, $personas):void
    {
        // Obtener horario de sauna
        $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
        $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

        // Inicializar variables
        $masajes               = $request->input('masajes');
        $contadorPersonas      = 1; // Contador de personas que reciben masaje
        $maxPersonasPorHorario = 2; // Máximo de personas por cada horario de masaje
        $totalMasajes          = $personas;

        // Crear la visita una sola vez
        Visita::create([
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

                Masaje::create([
                    'horario_masaje'  => Carbon::createFromFormat('H:i', $horario['horario_masaje']),
                    'tipo_masaje'     => $horario['tipo_masaje'],
                    'id_lugar_masaje' => $horario['id_lugar_masaje'] ?? 1,
                    'persona'         => $contadorPersonas,
                    'id_reserva'       => $reserva->id,
                ]);
                $contadorPersonas++;

            }
        }

        session()->forget(['masajesExtra', 'almuerzosExtra', 'cantidadMasajesExtra']);
    }

    private function spaSinMasajes(Request $request, Reserva $reserva):void
    {
        foreach ($request->input('spas') as $indexSpa => $spa) {
            // Validar que el horario_sauna exista en el arreglo actual
            if (isset($spa['horario_sauna'])) {
                $horarioSauna  = Carbon::createFromFormat('H:i', $spa['horario_sauna']);
                $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

                // Crear una visita para cada SPA
                Visita::create([
                    'id_reserva'     => $reserva->id,
                    'horario_sauna'  => $horarioSauna,
                    'horario_tinaja' => $horarioTinaja,
                    'id_ubicacion'   => $request->input('id_ubicacion'),
                    'trago_cortesia' => $request->input('trago_cortesia'),
                    'observacion'    => $request->input('observacion'),
                ]);

            }
        }

        session()->forget(['masajesExtra', 'almuerzosExtra', 'cantidadMasajesExtra']);
    }

    private function spasConMasajes(Request $request, Reserva $reserva, $personas):void
    {
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
                Visita::create([
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

                Masaje::create([
                    'horario_masaje'  => Carbon::createFromFormat('H:i', $horario['horario_masaje']),
                    'tipo_masaje'     => $horario['tipo_masaje'],
                    'id_lugar_masaje' => $horario['id_lugar_masaje'] ?? 1,
                    'persona'         => $contadorPersonas,
                    'id_reserva'       => $reserva->id,
                ]);
                $contadorPersonas++;

            }
        }

        session()->forget(['masajesExtra', 'almuerzosExtra', 'cantidadMasajesExtra']);
    }

    private function sinData(Request $request, Reserva $reserva, $incluyeMasaje, $personas):void
    {
        $cantidadPersonas     = $reserva->cantidad_personas;
        $maxPersonasPorVisita = 5;
        $visita               = null;

        for ($i = 1; $i <= ceil($cantidadPersonas / $maxPersonasPorVisita); $i++) {
            Visita::create([
                'horario_sauna'  => null,
                'horario_tinaja' => null,
                'trago_cortesia' => $request->input('trago_cortesia') ?? null,
                'observacion'    => null,
                'id_reserva'     => $reserva->id,
                'id_ubicacion'   => $request->input('id_ubicacion') ?? null,
            ]);
        }

        if ($incluyeMasaje) {
            for ($i = 1; $i <= $personas; $i++) {
                Masaje::create([
                    'horario_masaje'  => null,
                    'tipo_masaje'     => null,
                    'id_lugar_masaje' => 1,
                    'persona'         => $i,
                    'id_reserva'      => $reserva->id,
                    'user_id'         => null,
                ]);
            }
        }
    }
}
