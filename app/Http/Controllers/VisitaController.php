<?php

namespace App\Http\Controllers;

use App\LugarMasaje;
use App\Mail\RegistroReservaMailable;
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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Asignacion de dias Hoy y Mañana
        $hoy = Carbon::today();
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
            'reservasHoy' => $reservasHoy,
            'reservasManana' => $reservasManana,
            //Reservas para la relacion con visitas
            // 'reservas' => Reserva::with('cliente', 'programa', 'user')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create($reserva)
    {
        $masajesExtra = session()->get('masajesExtra');
        $almuerzosExtra = session()->get('almuerzosExtra');

        $reserva = Reserva::findOrFail($reserva);
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
            return !in_array($ubicacion->nombre, $ubicacionesOcupadas);
        })->values();

        // ===============================HORAS=SPA==============================================
        // Horarios disponibles de 10:00 a 18:30 SPA
        $horaInicio = new \DateTime('10:00');
        $horaFin = new \DateTime('18:30');
        $intervalo = new \DateInterval('PT30M');
        $horarios = [];

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
        $horaFinMasajes = new \DateTime('19:00');
        $duracionMasaje = new \DateInterval('PT30M'); // 30 minutos de duración
        $intervalos = new \DateInterval('PT10M'); // 10 minutos de intervalos entre sesiones
        $horarios = [];

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
            'reserva' => $reserva,
            'ubicaciones' => $ubicaciones,
            'lugares' => LugarMasaje::all(),
            'servicios' => $serviciosDisponibles,
            'horarios' => $horariosDisponiblesSPA,
            'horasMasaje' => $horariosDisponiblesMasajes,
            'entradas' => $entradas,
            'fondos' => $fondos,
            'acompañamientos' => $acompañamientos,
            'masajesExtra' => $masajesExtra,
            'almuerzosExtra' => $almuerzosExtra,
        ]);
    }

    public function createOLD($reserva)
    {
        $masajesExtra = session()->get('masajesExtra');
        $almuerzosExtra = session()->get('almuerzosExtra');

        $reserva = Reserva::findOrFail($reserva);
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
            return !in_array($ubicacion->nombre, $ubicacionesOcupadas);
        })->values();

        // ===============================HORAS=SPA==============================================
        // Horarios disponibles de 10:00 a 18:30 SPA
        $horaInicio = new \DateTime('10:00');
        $horaFin = new \DateTime('18:30');
        $intervalo = new \DateInterval('PT30M');
        $horarios = [];

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
        $horaFinMasajes = new \DateTime('19:00');
        $duracionMasaje = new \DateInterval('PT30M'); // 30 minutos de duración
        $intervalos = new \DateInterval('PT10M'); // 10 minutos de intervalos entre sesiones
        $horarios = [];

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
                return !is_null($hora); // Filtra valores nulos
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
            'reserva' => $reserva,
            'ubicaciones' => $ubicaciones,
            'lugares' => LugarMasaje::all(),
            'servicios' => $serviciosDisponibles,
            'horarios' => $horariosDisponiblesSPA,
            'horasMasaje' => $horariosDisponiblesMasajes,
            'entradas' => $entradas,
            'fondos' => $fondos,
            'acompañamientos' => $acompañamientos,
            'masajesExtra' => $masajesExtra,
            'almuerzosExtra' => $almuerzosExtra,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Reserva $reserva)
    {
        $request->validate([
            'horario_sauna' => 'nullable|string', // Para 1 horario SPA
            'horario_masaje' => 'nullable|string', // Caso de datos simples
            'tipo_masaje' => 'nullable|string',
            'id_ubicacion' => 'required|string',
            'trago_cortesia' => 'required|string',
            'spas.*.horario_sauna' => 'nullable|string', // Para arreglos de SPA
            'masajes.*.horario_masaje' => 'nullable|string', // Para arreglos de masajes
            'masajes.*.tipo_masaje' => 'nullable|string',
            'masajes.*.id_lugar_masaje' => 'nullable|string',
            'menus.*.id_producto_entrada' => 'nullable|integer',
            'menus.*.id_producto_fondo' => 'nullable|string',
            'menus.*.id_producto_acompanamiento' => 'nullable|integer',
            'menus.*.alergias' => 'nullable|string',
            'menus.*.observacion' => 'nullable|string',
        ]);

        // dd(is_array($request->input('masajes')));
        $visita = null;
        $cliente = null;
        $programa = $reserva->programa;
        $almuerzosExtra = session()->get('almuerzosExtra');

        $almuerzoIncluido = $programa->servicios->pluck('nombre_servicio')->toArray();

        try {
            DB::transaction(function () use ($request, &$reserva, &$visita, &$cliente, $almuerzoIncluido, $almuerzosExtra) {

                $cliente = $reserva->cliente;

                // Caso 1: Solo SPA (sin masajes)
                if (!$request->has('masajes') && $request->has('horario_sauna')) {
                    // Convertir horario_sauna a objeto Carbon
                    $horarioSauna = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

                    // Crear una visita con solo SPA
                    $visita = Visita::create([
                        'id_reserva' => $reserva->id,
                        'horario_sauna' => $horarioSauna, // Horario del SPA
                        'horario_tinaja' => $horarioTinaja, // Horario de tinaja
                        'horario_masaje' => null, // No hay masajes
                        'tipo_masaje' => null, // No hay masajes
                        'id_ubicacion' => $request->input('id_ubicacion'),
                        'id_lugar_masaje' => null, // No hay masajes
                        'trago_cortesia' => $request->input('trago_cortesia'),
                        'observacion' => $request->input('observacion'),
                    ]);
                }

// Caso 2: 1 horario SPA con arreglo de masajes
                if ($request->has('masajes') && $request->has('horario_sauna')) {
                    // Obtener horario de sauna
                    $horarioSauna = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

                    // Inicializar contador para los masajes
                    $masajes = $request->input('masajes');
                    $totalMasajes = count($masajes); // Total de masajes
                    $maxMasajesPorSpa = 3; // Máximo de masajes por horario de SPA

                    // Procesar masajes para el horario de sauna
                    $contadorMasajes = 0; // Índice para recorrer los masajes
                    $masajesAsignados = 0; // Contador de masajes asignados a este SPA

                    foreach ($masajes as $index => $masaje) {
                        if ($masajesAsignados < $maxMasajesPorSpa) {
                            // Crear la visita para este masaje asociado al SPA
                            $visita = Visita::create([
                                'id_reserva' => $reserva->id,
                                'horario_sauna' => $horarioSauna, // Asociar el horario de SPA
                                'horario_tinaja' => $horarioTinaja,
                                'horario_masaje' => Carbon::createFromFormat('H:i', $masaje['horario_masaje']),
                                'tipo_masaje' => $masaje['tipo_masaje'],
                                'id_ubicacion' => $request->input('id_ubicacion'),
                                'id_lugar_masaje' => $masaje['id_lugar_masaje'] ?? null,
                                'trago_cortesia' => $request->input('trago_cortesia'),
                                'observacion' => $request->input('observacion'),
                            ]);

                            $contadorMasajes++;
                            $masajesAsignados++;
                        } else {
                            break; // Si ya se asignaron 3 masajes, salir del bucle
                        }
                    }

                    // Validar si quedaron masajes sin asignar (esto no debería ocurrir con esta lógica)
                    if ($contadorMasajes < $totalMasajes) {
                        echo "Advertencia: Quedaron masajes sin asignar.<br>";
                    }
                }

                // Caso 3: Arreglos de SPA sin masajes
                if (!$request->has('masajes') && $request->has('spas')) {
                    foreach ($request->input('spas') as $indexSpa => $spa) {
                        // Validar que el horario_sauna exista en el arreglo actual
                        if (isset($spa['horario_sauna'])) {
                            $horarioSauna = Carbon::createFromFormat('H:i', $spa['horario_sauna']);
                            $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

                            // Crear una visita para cada SPA
                            $visita = Visita::create([
                                'id_reserva' => $reserva->id,
                                'horario_sauna' => $horarioSauna,
                                'horario_tinaja' => $horarioTinaja,
                                'horario_masaje' => null, // No hay masajes
                                'tipo_masaje' => null, // No hay masajes
                                'id_ubicacion' => $request->input('id_ubicacion'),
                                'id_lugar_masaje' => null, // No hay lugar de masaje
                                'trago_cortesia' => $request->input('trago_cortesia'),
                                'observacion' => $request->input('observacion'),
                            ]);

                        } else {
                            // Opcional: Manejo de errores si el horario_sauna no está definido
                            echo "SPA Index: $indexSpa no tiene horario_sauna definido.<br>";
                            break;
                        }
                    }
                }

                // Caso 4: Arreglos de SPA y masajes
                if ($request->has('masajes') && $request->has('spas')) {
                    $contadorMasajes = 0; // Índice para recorrer masajes
                    $masajes = $request->input('masajes');
                    $totalMasajes = count($masajes); // Total de masajes (7)
                    $spas = array_reverse($request->input('spas')); // Invertir el orden de los spas
                    $totalSpas = count($spas); // Total de spas (3)

                    // Calcular cuántos masajes deben asignarse a cada SPA
                    $masajesPorSpa = (int) floor($totalMasajes / $totalSpas);
                    $masajesRestantes = $totalMasajes % $totalSpas;

                    foreach ($spas as $indexSpa => $spa) {
                        $horarioSauna = Carbon::createFromFormat('H:i', $spa['horario_sauna']);
                        $horarioTinaja = $horarioSauna->copy()->addMinutes(15);
                        $masajesAsignados = 0; // Contador para los masajes asignados a este SPA

                        // Determinar cuántos masajes asignar a este SPA
                        $masajesParaEsteSpa = $masajesPorSpa;

                        // Si es el último SPA en la iteración, asignar los masajes sobrantes
                        if ($indexSpa === array_key_last($spas)) {
                            $masajesParaEsteSpa += $masajesRestantes;
                        }

                        // Asignar masajes a este SPA
                        while ($contadorMasajes < $totalMasajes && $masajesAsignados < $masajesParaEsteSpa) {
                            $masaje = $masajes[$contadorMasajes];
                            $visita = Visita::create([
                                'id_reserva' => $reserva->id,
                                'horario_sauna' => $horarioSauna,
                                'horario_tinaja' => $horarioTinaja,
                                'horario_masaje' => Carbon::createFromFormat('H:i', $masaje['horario_masaje']),
                                'tipo_masaje' => $masaje['tipo_masaje'],
                                'id_ubicacion' => $request->input('id_ubicacion'),
                                'id_lugar_masaje' => $masaje['id_lugar_masaje'] ?? null,
                                'trago_cortesia' => $request->input('trago_cortesia'),
                                'observacion' => $request->input('observacion'),
                            ]);

                            $contadorMasajes++;
                            $masajesAsignados++;
                        }

                        // Crear una visita solo para el SPA si no se asignaron masajes
                        if ($masajesAsignados === 0) {
                            $visita = Visita::create([
                                'id_reserva' => $reserva->id,
                                'horario_sauna' => $horarioSauna,
                                'horario_tinaja' => $horarioTinaja,
                                'horario_masaje' => null,
                                'tipo_masaje' => null,
                                'id_ubicacion' => $request->input('id_ubicacion'),
                                'id_lugar_masaje' => null,
                                'trago_cortesia' => $request->input('trago_cortesia'),
                                'observacion' => $request->input('observacion'),
                            ]);
                        }
                    }

                    // Validar si quedaron masajes sin asignar (esto no debería ocurrir con esta lógica)
                    if ($contadorMasajes < $totalMasajes) {
                        echo "Advertencia: Quedaron masajes sin asignar.<br>";
                    }
                }

                if (!in_array('Almuerzo', $almuerzoIncluido) && !$almuerzosExtra) {

                } else {

                    foreach ($request->menus as $menu) {
                        Menu::create([
                            'id_visita' => $visita->id,
                            'id_producto_entrada' => $menu['id_producto_entrada'],
                            'id_producto_fondo' => $menu['id_producto_fondo'],
                            'id_producto_acompanamiento' => $menu['id_producto_acompanamiento'],
                            'alergias' => $menu['alergias'],
                            'observacion' => $menu['observacion'],
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

    /**
     * Display the specified resource.
     *
     * @param  \App\Visita  $visita
     * @return \Illuminate\Http\Response
     */
    public function show(Visita $visitum)
    {
        dd($visitum->reserva->programa->servicios()->whereIn('nombre_servicio', ['Sauna', 'Saunas', 'sauna', 'saunas'])->exists());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Visita  $visita
     * @return \Illuminate\Http\Response
     */
    public function edit(Visita $visitum)
    {
        return view('themes.backoffice.pages.visita.edit', [
            'visita' => $visitum,
        ]);
    }

    public function update(Request $request, Visita $visitum)
    {
        //
    }

    public function destroy(Visita $visitum)
    {
        //
    }

    public function edit_ubicacion(Visita $visitum)
    {
        $fechaSeleccionada = \Carbon\Carbon::createFromFormat('d-m-Y', $visitum->reserva->fecha_visita)->format('Y-m-d');
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

        return view('themes.backoffice.pages.visita.edit_ubicacion', [
            'visita' => $visitum,
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
