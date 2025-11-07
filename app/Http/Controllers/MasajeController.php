<?php

namespace App\Http\Controllers;

use App\CategoriaMasaje;
use App\LugarMasaje;
use App\Masaje;
use App\PrecioTipoMasaje;
use App\Reserva;
use App\TipoMasaje;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class MasajeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function indexload()
    {
        $masajes = Masaje::with(['visita.reserva'])->get();
        dd($masajes);
    }

    public function index()
    {
        // Asignación de la fecha actual
        $fechaActual = Carbon::now()->startOfDay();


        $reservas = Reserva::where('fecha_visita', '>=', $fechaActual)
            ->with([
                'masajes' => function($q){
                    $q->orderBy('horario_masaje','asc')->orderBy('persona','asc');
                },
                'masajes.lugarMasaje',
                'masajes.user',
                'masajes.reserva.cliente',
                'cliente',
                'venta.consumo',
                'venta.consumo.detalleServiciosExtra',
                'venta.consumo.detalleServiciosExtra.precioTipoMasaje',
            ])
            ->select('reservas.*')
            ->selectSub(
                DB::table('masajes')
                    ->whereColumn('masajes.id_reserva', 'reservas.id')
                    ->orderBy('horario_masaje', 'asc')
                    ->limit(1)
                    ->select('horario_masaje'),
                'first_horario_masaje'
            )
            ->orderBy('fecha_visita', 'asc')
            ->orderBy('first_horario_masaje', 'asc')
            ->get();

            // dd($reservas);

        // Agrupar reservas por fecha
        $reservasPorDia = $reservas->groupBy(function ($reserva) {
            return Carbon::parse($reserva->fecha_visita)->format('d-m-Y');
        });

        // Paginación manual por días
        $perPage = 1; // Número de días por página
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $todasLasFechas = $reservasPorDia->keys()->values();

        $currentItems = $reservasPorDia->slice(($currentPage - 1) * $perPage, $perPage)->all();

        // Crear el paginador manualmente
        $reservasPaginadas = new LengthAwarePaginator($currentItems, $reservasPorDia->count(), $perPage, $currentPage, [
            'path' => request()->url(),
        ]);

        $distribucionHorarios = [];

        foreach ($reservasPaginadas as $fecha => $reservaciones) {
            foreach ($reservaciones as $reserva) {
                $cantidadPersonas = $reserva->cantidad_personas;
                $horarioMasaje = $reserva->horario_masaje;
                $distribucion = [];
                $indexHorario = 0;

                // Distribuir las personas en los horarios de masajes
                while ($cantidadPersonas > 0) {
                    // Asigna hasta 2 personas por cada horario
                    $personasEnEsteHorario = min($cantidadPersonas, 2);

                    // Calcula el horario ajustando por bloques de 30 minutos
                    $nuevoHorario = date('H:i', strtotime($horarioMasaje));

                    // Agrega la distribución de personas al horario
                    $distribucion[] = [
                        'horario' => $nuevoHorario,
                        'personas' => $personasEnEsteHorario,
                    ];

                    // Resta las personas asignadas y aumenta el índice del horario
                    $cantidadPersonas -= $personasEnEsteHorario;
                    $indexHorario++;
                }

                $distribucionHorarios[$reserva->id] = $distribucion;
            }
        }




        // $fechaSeleccionada = \Carbon\Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d');

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
        ->where('reservas.fecha_visita', $fechaActual)
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

        $user = auth()->user();


        $contador = Masaje::where('user_id', $user->id)
                ->whereHas('reserva', function ($query) use ($fechaActual){
                    $query->whereDate('fecha_visita', $fechaActual);
                })
                ->with('reserva')
                ->get()
                ->count();

        // dd($contador);


        // Retorno de la vista
        return view('themes.backoffice.pages.masaje.index', [
            'reservasPaginadas' => $reservasPaginadas,
            'distribucionHorarios' => $distribucionHorarios,
            'horasDisponibles' => $horariosDisponiblesMasajes,
            'fechasPaginadas' => $todasLasFechas,
            'contador' => $contador,
            'lugares' => LugarMasaje::all()
        ]);
    }




    public function indexFuncionando()
    {
        // Asignación de la fecha actual
        $fechaActual = Carbon::now()->startOfDay();


        $reservas = Reserva::where('fecha_visita', '>=', $fechaActual)
            ->with([
                'masajes',
                'masajes.lugarMasaje',
                'cliente',
                'venta.consumo',
                'venta.consumo.detalleServiciosExtra',
                'venta.consumo.detalleServiciosExtra.precioTipoMasaje',
            ])
            ->select('reservas.*')
            ->selectSub(
                DB::table('masajes')
                    ->whereColumn('masajes.id_reserva', 'reservas.id')
                    ->orderBy('horario_masaje', 'asc')
                    ->limit(1)
                    ->select('horario_masaje'),
                'first_horario_masaje'
            )
            ->orderBy('fecha_visita', 'asc')
            ->orderBy('first_horario_masaje', 'asc')
            ->get();


        // Agrupar reservas por fecha
        $reservasPorDia = $reservas->groupBy(function ($reserva) {
            return Carbon::parse($reserva->fecha_visita)->format('d-m-Y');
        });

        // Paginación manual por días
        $perPage = 1; // Número de días por página
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $todasLasFechas = $reservasPorDia->keys()->values();

        $currentItems = $reservasPorDia->slice(($currentPage - 1) * $perPage, $perPage)->all();

        // Crear el paginador manualmente
        $reservasPaginadas = new LengthAwarePaginator($currentItems, $reservasPorDia->count(), $perPage, $currentPage, [
            'path' => request()->url(),
        ]);

        $distribucionHorarios = [];

        foreach ($reservasPaginadas as $fecha => $reservaciones) {
            foreach ($reservaciones as $reserva) {
                $cantidadPersonas = $reserva->cantidad_personas;
                $horarioMasaje = $reserva->horario_masaje;
                $distribucion = [];
                $indexHorario = 0;

                // Distribuir las personas en los horarios de masajes
                while ($cantidadPersonas > 0) {
                    // Asigna hasta 2 personas por cada horario
                    $personasEnEsteHorario = min($cantidadPersonas, 2);

                    // Calcula el horario ajustando por bloques de 30 minutos
                    $nuevoHorario = date('H:i', strtotime($horarioMasaje));

                    // Agrega la distribución de personas al horario
                    $distribucion[] = [
                        'horario' => $nuevoHorario,
                        'personas' => $personasEnEsteHorario,
                    ];

                    // Resta las personas asignadas y aumenta el índice del horario
                    $cantidadPersonas -= $personasEnEsteHorario;
                    $indexHorario++;
                }

                $distribucionHorarios[$reserva->id] = $distribucion;
            }
        }




        // $fechaSeleccionada = \Carbon\Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d');

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
        ->where('reservas.fecha_visita', $fechaActual)
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

        $user = auth()->user();


        $contador = Masaje::where('user_id', $user->id)
                ->whereHas('reserva', function ($query) use ($fechaActual){
                    $query->whereDate('fecha_visita', $fechaActual);
                })
                ->with('reserva')
                ->get()
                ->count();

        // dd($contador);


        // Retorno de la vista
        return view('themes.backoffice.pages.masaje.index', [
            'reservasPaginadas' => $reservasPaginadas,
            'distribucionHorarios' => $distribucionHorarios,
            'horasDisponibles' => $horariosDisponiblesMasajes,
            'fechasPaginadas' => $todasLasFechas,
            'contador' => $contador
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // // Validar la solicitud
        // $request->validate([
        //     'id_visita' => 'required|exists:visitas,id',
        //     'persona_numero' => 'required|integer|min:1',
        // ]);

        // $masaje = Masaje::create([
        //     'persona' => $request->persona_numero,
        //     'id_visita' => $request->id_visita,
        //     'user_id' => auth()->id(),
        // ]);

        // // Redirigir con un mensaje de éxito
        // Alert::toast('Masaje asignado correctamente', 'success');
        // return redirect()->back()->with('success', 'Masaje asignado correctamente');
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
        //
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
        $masaje = Masaje::find($id);

        if (!$masaje) {
            return redirect()->back()->with('error', 'Masaje no encontrado');
        }

        $masaje->update([
            'user_id'=>auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Masaje asignado correctamente');
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

    public function asignar_multiples(Request $request)
    {
        $user = auth()->user();

        // Verificar que el usuario tenga el rol adecuado
        if (!$user->has_role(config('app.masoterapeuta_role'))) {
            return back()->with('error', 'No autorizado.');
        }

        // Validar que existan masajes seleccionados
        $masajes = $request->input('masajes_seleccionados', []);
        if (empty($masajes)) {
            return back()->with('error', 'No seleccionaste ningún masaje.');
        }

        $conteo = 0;

        // Idealmente usar whereIn para eficiencia si no necesitas lógica por masaje
        foreach ($masajes as $masajeId) {
            $masaje = Masaje::whereNull('user_id') // Solo asignar si no tiene usuario
                            ->find($masajeId);

            if ($masaje) {
                $masaje->update([
                    'user_id' => $user->id,
                ]);
                $conteo++;
            }
        }

        return back()->with('success', $conteo.' Masajes asignados exitosamente.');

    }

    public function index_valor()
    {
        // $masajes = TipoMasaje::with(['categoria', 'precios'])->get();

    //         $masajes = TipoMasaje::with([
    //     'categoria',
    //     'precios' => function ($q) {
    //         $q->orderBy('duracion_minutos'); // 30, 45, 60...
    //     }
    // ])->whereHas('precios')
    //   ->get();

    //     return view('themes.backoffice.pages.masaje.admin.index_valor', compact('masajes'));

        $masajes = TipoMasaje::activos()
        ->with(['categoria','precios'=>function($q){return $q->orderBy('duracion_minutos');}])
        ->whereHas('precios')->get();

    return view('themes.backoffice.pages.masaje.admin.index_valor', compact('masajes'));
    }

    public function index_valor_inactivos()
    {
        $masajes = TipoMasaje::inactivos()
            ->with(['categoria','precios'=>function($q){return $q->orderBy('duracion_minutos');}])->get();

        return view('themes.backoffice.pages.masaje.admin.index_valor_inactivos', compact('masajes'));
    }

    public function cambiarEstado(Request $request, TipoMasaje $tipoMasaje)
    {
        $request->validate(['activo' => 'required|boolean']);
        $tipoMasaje->update(['activo' => (int)$request->activo]);
        // return back()->with('status','Estado actualizado');
        return back()->with('status', $request->activo ? 'Masaje activado' : 'Masaje desactivado');

    }

    public function valor_masaje_create(Request $request)
    {
        $tipos = TipoMasaje::all();

        return view('themes.backoffice.pages.masaje.valor.create', compact('tipos'));
    }

    public function valor_masaje_store(Request $request)
    {
        $tipo = TipoMasaje::findOrFail($request->id_tipo_masaje);
        $request->merge([

            'precio_unitario'    => (int) str_replace(['$', '.', ','], "", $request->precio_unitario),
            'precio_pareja'    => (int) str_replace(['$', '.', ','], "", $request->precio_pareja),
            'pago_masoterapeuta'    => (int) str_replace(['$', '.', ','], "", $request->pago_masoterapeuta),

        ]);

        $precio_pareja = $request->precio_pareja > 0 ? $request->precio_pareja : null;

        // dd($request->all(), $precio_pareja, $tipo->nombre);

        PrecioTipoMasaje::create([
            'id_tipo_masaje' => $request->id_tipo_masaje,
            'duracion_minutos' => $request->duracion_minutos,
            'precio_unitario' => $request->precio_unitario,
            'precio_pareja' => $precio_pareja,
            'pago_masoterapeuta' => $request->pago_masoterapeuta
        ]);

        return redirect()->route('backoffice.masajes.valores')->with('success','Se asigno el valor al tipo '.$tipo->nombre);
    }


    public function valor_masaje_edit(Request $request, $id)
    {
        $precio = PrecioTipoMasaje::findOrFail($id);
        $tipos = TipoMasaje::all();

        return view('themes.backoffice.pages.masaje.valor.edit', compact('tipos', 'precio'));
    }

    public function valor_masaje_update(Request $request, PrecioTipoMasaje $precio)
    {
        $tipo = TipoMasaje::findOrFail($request->id_tipo_masaje);
        $request->merge([

            'precio_unitario'    => (int) str_replace(['$', '.', ','], "", $request->precio_unitario),
            'precio_pareja'    => (int) str_replace(['$', '.', ','], "", $request->precio_pareja),
            'pago_masoterapeuta'    => (int) str_replace(['$', '.', ','], "", $request->pago_masoterapeuta),

        ]);

        $precio_pareja = $request->precio_pareja > 0 ? $request->precio_pareja : null;

        // dd([
        //     'id_tipo_masaje' => $request->id_tipo_masaje,
        //     'duracion_minutos' => $request->duracion_minutos,
        //     'precio_unitario' => $request->precio_unitario,
        //     'precio_pareja' => $precio_pareja,
        //     'pago_masoterapeuta' => $request->pago_masoterapeuta
        // ], $precio);

        $precio->update([
            'id_tipo_masaje' => $request->id_tipo_masaje,
            'duracion_minutos' => $request->duracion_minutos,
            'precio_unitario' => $request->precio_unitario,
            'precio_pareja' => $precio_pareja,
            'pago_masoterapeuta' => $request->pago_masoterapeuta
        ]);

        return redirect()->route('backoffice.masajes.valores')->with('success','Se actualizó el valor al tipo '.$tipo->nombre);
    }


}
