<?php

namespace App\Http\Controllers;

use App\AnularSueldoUsuario;
use App\Consumo;
use App\Masaje;
use App\Propina;
use App\RangoSueldoRole;
use App\Sueldo;
use App\SueldoPagado;
use App\User;
use App\VentaDirecta;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RealRashid\SweetAlert\Facades\Alert;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SueldoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function indexOld(Request $request)
    // {
    //     $mes = $request->input('mes', now()->month);
    //     $anio = $request->input('anio', now()->year);
    
    //     // Obtener usuarios que tengan al menos un sueldo ese mes/año
    //     $usuarios = User::whereHas('sueldos', function ($query) use ($mes, $anio) {
    //         $query->whereMonth('dia_trabajado', $mes)
    //               ->whereYear('dia_trabajado', $anio);
    //     })
    //     ->with(['sueldos' => function ($query) use ($mes, $anio) {
    //         $query->whereMonth('dia_trabajado', $mes)
    //               ->whereYear('dia_trabajado', $anio)
    //               ->orderBy('dia_trabajado', 'asc');
    //     }])
    //     ->get();

    //     // Obtener todos los meses/años únicos en que el usuario trabajó
    //     $fechasDisponibles = Sueldo::selectRaw('MONTH(dia_trabajado) as mes, YEAR(dia_trabajado) as anio')
    //     ->groupBy('mes', 'anio')
    //     ->orderBy('anio', 'desc')
    //     ->orderBy('mes', 'desc')
    //     ->get();
    
    //     return view('themes.backoffice.pages.sueldo.index', [
    //         'usuarios' => $usuarios,
    //         'mes' => $mes,
    //         'anio' => $anio,
    //         'fechasDisponibles' => $fechasDisponibles
    //     ]);
    // }


    public function OLDindex(Request $request)
    {
        $mes = $request->input('mes', now()->month);
        $anio = $request->input('anio', now()->year);

        $sueldos = Sueldo::with('user')
            ->whereMonth('dia_trabajado', $mes)
            ->whereYear('dia_trabajado', $anio)
            ->orderBy('dia_trabajado')
            ->get();

        $semanas = [];

        foreach ($sueldos as $sueldo) {
            $fecha = Carbon::parse($sueldo->dia_trabajado);
            $inicioSemana = $fecha->copy()->startOfWeek(Carbon::MONDAY);
            $finSemana = $fecha->copy()->endOfWeek(Carbon::SUNDAY);

            $rango = $inicioSemana->format('d M') . ' - ' . $finSemana->format('d M');

            $roles = $sueldo->user->list_roles();

            $userId = $sueldo->user->id;
            $userName = $sueldo->user->name;
            $userRole = $sueldo->user->roles;

            if (!isset($semanas[$rango])) {
                $semanas[$rango] = [];
            }
            
            if (!isset($semanas[$rango][$userId])) {
                $semanas[$rango][$userId] = [
                    'role' => $roles,
                    'name' => $userName,
                    'dias' => 0,
                    'sueldos' => 0,
                    'propinas' => 0,
                    'total' => 0,
                    'user_id' => $userId,
                    'inicio' => $inicioSemana->format('Y-m-d'),
                    'fin' => $finSemana->format('Y-m-d')
                ];
            }

            $semanas[$rango][$userId]['dias'] += 1;
            $semanas[$rango][$userId]['sueldos'] += $sueldo->valor_dia;
            if (in_array($roles, ['Masoterapeuta'])) {
                $semanas[$rango][$userId]['propinas'] = 0;
            }else{
                $semanas[$rango][$userId]['propinas'] += $sueldo->sub_sueldo - $sueldo->valor_dia;
            }
            $semanas[$rango][$userId]['total'] += $sueldo->total_pagar;
        }

        // ordenar las semanas cronológicamente
        uksort($semanas, function ($a, $b) use ($anio) {
            $dateA = Carbon::createFromFormat('d M Y', substr($a, 0, 6) . $anio);
            $dateB = Carbon::createFromFormat('d M Y', substr($b, 0, 6) . $anio);
            return $dateA->timestamp <=> $dateB->timestamp;
        });

        $fechasDisponibles = Sueldo::selectRaw('MONTH(dia_trabajado) as mes, YEAR(dia_trabajado) as anio')
            ->groupBy('mes', 'anio')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        $pagosRealizados = SueldoPagado::select('*')->get();


        return view('themes.backoffice.pages.sueldo.index', [
            'semanas' => $semanas,
            'mes' => $mes,
            'anio' => $anio,
            'fechasDisponibles' => $fechasDisponibles,
            'pagosRealizados' => $pagosRealizados
        ]);
    }




    public function index(Request $request)
    {

        $mes  = $request->input('mes', now()->month);
        $anio = $request->input('anio', now()->year);

        $sueldos = Sueldo::with('user')
            ->whereMonth('dia_trabajado', $mes)
            ->whereYear('dia_trabajado', $anio)
            ->orderBy('dia_trabajado')
            ->get();

        $semanas = [];

        foreach ($sueldos as $sueldo) {
            $fecha = Carbon::parse($sueldo->dia_trabajado);
            $inicioSemana = $fecha->copy()->startOfWeek(Carbon::MONDAY);
            $finSemana    = $fecha->copy()->endOfWeek(Carbon::SUNDAY);

            $rango = $inicioSemana->format('d M') . ' - ' . $finSemana->format('d M');
            $roles  = $sueldo->user->list_roles();
            $esMaso = is_array($roles) ? in_array('Masoterapeuta', $roles)
                                    : (stripos((string)$roles, 'Masoterapeuta') !== false);

            $userId   = $sueldo->user->id;
            $userName = $sueldo->user->name;

            if (!isset($semanas[$rango])) {
                $semanas[$rango] = [];
            }

            if (!isset($semanas[$rango][$userId])) {
                $semanas[$rango][$userId] = [
                    'role'    => $roles,
                    'name'    => $userName,
                    'dias'    => 0,   // aquí guardaremos "días" o "masajes" según rol
                    'sueldos' => 0,
                    'propinas'=> 0,
                    'bono'=> 0,
                    'motivo' => '',
                    'total'   => 0,
                    'user_id' => $userId,
                    'inicio'  => $inicioSemana->format('Y-m-d'),
                    'fin'     => $finSemana->format('Y-m-d'),
                ];

                // Si es masoterapeuta, calculamos una sola vez los MASAJES de la semana
                if ($esMaso) {
                    // $ini = $inicioSemana->toDateString();
                    // $fin = $finSemana->toDateString();

                    // // contamos los masajes según proporción total_pagar / valor_dia
                    // $cantMasajes = DB::table('sueldos')
                    //     ->whereBetween('dia_trabajado', [$ini, $fin])
                    //     ->where('id_user', $userId)
                    //     ->selectRaw('SUM(total_pagar / valor_dia) as cantidad')
                    //     ->value('cantidad');

                    // $semanas[$rango][$userId]['dias'] = (int) $cantMasajes; // “días” = masajes


                    $ini = $inicioSemana->toDateString();
                    $fin = $finSemana->toDateString();

                    $cantMasajes = DB::table('masajes as m')
                        ->join('reservas as r', 'r.id', '=', 'm.id_reserva')
                        ->whereBetween('r.fecha_visita', [$ini, $fin])
                        ->where('m.user_id', $userId)       // masoterapeuta
                        ->count();                           // cada fila = 1 masaje (incluye pareja como 2)

                    $semanas[$rango][$userId]['dias'] = (int) $cantMasajes; // “días” = masajes
                }
            }

            if (!$esMaso) {
                $semanas[$rango][$userId]['dias'] += 1;
            }

            $semanas[$rango][$userId]['sueldos']  += $sueldo->valor_dia;
            $semanas[$rango][$userId]['propinas'] += $esMaso ? 0 : ($sueldo->sub_sueldo - $sueldo->valor_dia);
            $semanas[$rango][$userId]['total']    += $sueldo->total_pagar;
        }

        // Orden cronológico de semanas
        uksort($semanas, function ($a, $b) use ($anio) {
            $dateA = Carbon::createFromFormat('d M Y', substr($a, 0, 6) . $anio);
            $dateB = Carbon::createFromFormat('d M Y', substr($b, 0, 6) . $anio);
            return $dateA->timestamp <=> $dateB->timestamp;
        });

        $fechasDisponibles = Sueldo::selectRaw('MONTH(dia_trabajado) as mes, YEAR(dia_trabajado) as anio')
            ->groupBy('mes', 'anio')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        // $pagosRealizados = SueldoPagado::select('*')->get();

        $pagosRealizados = SueldoPagado::all();

        foreach ($pagosRealizados as $pago) {

            $inicioSemana = Carbon::parse($pago->semana_inicio);
            $finSemana    = Carbon::parse($pago->semana_fin);

            $rango = $inicioSemana->format('d M') . ' - ' . $finSemana->format('d M');
            $userId = $pago->user_id;

            if (isset($semanas[$rango]) && isset($semanas[$rango][$userId])) {
                $semanas[$rango][$userId]['bono']   = (int) $pago->bono;
                $semanas[$rango][$userId]['motivo'] = $pago->motivo;

                // Si el bono debe sumarse al total de la semana:
                $semanas[$rango][$userId]['total'] += (int) $pago->bono;
            }
        }

        return view('themes.backoffice.pages.sueldo.index', compact(
            'semanas','mes','anio','fechasDisponibles','pagosRealizados'
        ));
    }




    public function adminViewSueldos(User $user, $anio, $mes, Request $request)
    {
        $userId = $user->id;
        
        // Obtener todos los meses/años únicos en que el usuario trabajó
        $fechasDisponibles = Sueldo::where('id_user', $userId)
            ->selectRaw('MONTH(dia_trabajado) as mes, YEAR(dia_trabajado) as anio')
            ->groupBy('mes', 'anio')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        // Si no se selecciona fecha, usar la más reciente disponible
        $fechaSeleccionada = $fechasDisponibles->first();
        $currentMonth = ($fechaSeleccionada ? $mes : now()->month);
        $currentYear = ($fechaSeleccionada ? $anio : now()->year);
        // $currentMonth = $request->input('mes', $fechaSeleccionada ? $fechaSeleccionada->mes : now()->month);
        // $currentYear = $request->input('anio', $fechaSeleccionada ? $fechaSeleccionada->anio : now()->year);

        // Obtener todos los sueldos del mes seleccionado
        $sueldos = Sueldo::where('id_user', $userId)
            ->whereMonth('dia_trabajado', $mes)
            ->whereYear('dia_trabajado', $anio)
            ->orderBy('dia_trabajado', 'asc')
            ->get();

        $masajesPorDia = [];
        foreach ($sueldos as $sueldo) {
            $fecha = Carbon::parse($sueldo->dia_trabajado)->toDateString();


            $masajesPorDia[$fecha] = DB::table('masajes as m')
                ->join('reservas as r', 'r.id', '=', 'm.id_reserva')
                ->where('m.user_id', $userId)
                ->whereDate('r.fecha_visita', $fecha)
                ->count();
        }


        // // Agrupar los sueldos por semana
        $sueldosAgrupados = $sueldos->groupBy(function ($sueldo) {
            $fecha = Carbon::parse($sueldo->dia_trabajado);
            $inicioSemana = $fecha->copy()->startOfWeek(Carbon::MONDAY);
            $finSemana = $fecha->copy()->endOfWeek(Carbon::SUNDAY);

            return $inicioSemana->format('d M') . ' - ' . $finSemana->format('d M');
        });


        if ($user->has_role('masoterapeuta')) {

            return view('themes.backoffice.pages.sueldo.admin_view_maso', [
                'sueldosAgrupados' => $sueldosAgrupados,
                'mes' => $mes,
                'anio' => $anio,
                'fechasDisponibles' => $fechasDisponibles,
                'user' => $user,
                'masajesPorDia' => $masajesPorDia
            ]);

        }else{
            
            return view('themes.backoffice.pages.sueldo.admin_view', [
                'sueldosAgrupados' => $sueldosAgrupados,
                'mes' => $mes,
                'anio' => $anio,
                'fechasDisponibles' => $fechasDisponibles,
                'user' => $user,
            ]);

        }


    }






    public function OLDview(User $user, Request $request)
    {
        $userId = $user->id;

        // Obtener mes y año del request o usar el mes y año actuales como predeterminado
        $currentMonth = $request->input('mes', now()->month);
        $currentYear = $request->input('anio', now()->year);

        // Filtrar registros por el mes seleccionado
        $sueldos = Sueldo::where('id_user', $userId)
            ->whereMonth('dia_trabajado', $currentMonth)
            ->whereYear('dia_trabajado', $currentYear)
            ->orderBy('dia_trabajado', 'asc')
            ->paginate(15); // Paginación con 10 registros por página

        // Verificar la autorización para al menos un sueldo
        // if ($sueldos->isNotEmpty()) {
            $this->authorize('view', $sueldos->first());
        // } else {
        //     abort(403);
        // }

        return view('themes.backoffice.pages.sueldo.view', [
            'sueldos' => $sueldos,
            'mes' => $currentMonth,
            'anio' => $currentYear,
            'user' => $user,
        ]);
    }

    public function view(User $user, Request $request)
    {
        $userId = $user->id;

        // Obtener todos los meses/años únicos en que el usuario trabajó
        $fechasDisponibles = Sueldo::where('id_user', $userId)
            ->selectRaw('MONTH(dia_trabajado) as mes, YEAR(dia_trabajado) as anio')
            ->groupBy('mes', 'anio')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        // Si no se selecciona fecha, usar la más reciente disponible
        $fechaSeleccionada = $fechasDisponibles->first();
        $currentMonth = $request->input('mes', $fechaSeleccionada ? $fechaSeleccionada->mes : now()->month);
        $currentYear = $request->input('anio', $fechaSeleccionada ? $fechaSeleccionada->anio : now()->year);

        // Obtener todos los sueldos del mes seleccionado
        $sueldos = Sueldo::where('id_user', $userId)
            ->whereMonth('dia_trabajado', $currentMonth)
            ->whereYear('dia_trabajado', $currentYear)
            ->orderBy('dia_trabajado', 'asc')
            ->get();

        if ($sueldos->isNotEmpty()) {
            $this->authorize('view', $sueldos->first());
        }

        // Agrupar los sueldos por semana
        $sueldosAgrupados = $sueldos->groupBy(function ($sueldo) {
            $fecha = Carbon::parse($sueldo->dia_trabajado);
            $inicioSemana = $fecha->copy()->startOfWeek(Carbon::MONDAY);
            $finSemana = $fecha->copy()->endOfWeek(Carbon::SUNDAY);

            return $inicioSemana->format('d M') . ' - ' . $finSemana->format('d M');
        });

        return view('themes.backoffice.pages.sueldo.view', [
            'sueldosAgrupados' => $sueldosAgrupados,
            'mes' => $currentMonth,
            'anio' => $currentYear,
            'fechasDisponibles' => $fechasDisponibles,
            'user' => $user,
        ]);
    }


    public function viewSueldos(User $user, Request $request)
    {
        $userId = $user->id;

        // Obtener todos los meses/años únicos en que el usuario trabajó
        $fechasDisponibles = Sueldo::where('id_user', $userId)
            ->selectRaw('MONTH(dia_trabajado) as mes, YEAR(dia_trabajado) as anio')
            ->groupBy('mes', 'anio')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        // Si no se selecciona fecha, usar la más reciente disponible
        $fechaSeleccionada = $fechasDisponibles->first();

        $currentMonth = $request->input('mes', $fechaSeleccionada ? $fechaSeleccionada->mes : now()->month);
        $currentYear = $request->input('anio', $fechaSeleccionada ? $fechaSeleccionada->anio : now()->year);
        

        $sueldos = Sueldo::where('id_user', $userId)
            ->whereMonth('dia_trabajado', $currentMonth)
            ->whereYear('dia_trabajado', $currentYear)
            ->orderBy('dia_trabajado', 'asc')
            ->paginate(15);

        if ($sueldos->isNotEmpty()) {
            $this->authorize('view', $sueldos->first());
        }

        return view('themes.backoffice.pages.sueldo.view', [
            'sueldos' => $sueldos,
            'mes' => $currentMonth,
            'anio' => $currentYear,
            'fechasDisponibles' => $fechasDisponibles,
            'user' => $user,
        ]);
    }


    
    public function detalle_diario(User $user, $anio, $mes, $dia )
    {
        $user = $user->load('sueldos', 'propinas');
        $fecha = Carbon::createFromDate($anio, $mes, $dia);

        $sueldo = Sueldo::where('id_user', $user->id)
            ->whereDate('dia_trabajado', $fecha)
            ->with('propina')
            ->first();

        // Obtener propinas del día con las relaciones necesarias
        $propinas = Propina::with('propinable')
        ->whereDate('fecha', $fecha)
        ->get();
    



        // Filtrar solo las asignaciones del usuario actual
        $asignaciones = collect();
        $total_propina_usuario = 0;

        // foreach ($propinas as $propina) {
        //     $pivot = $propina->users()->where('users.id', $user->id)->first();
        //     if ($pivot) {
        //         $asignaciones->push((object)[
        //             'nombre_cliente' => optional($propina->propinable->venta->reserva->cliente)->nombre_cliente ?? 'Desconocido',
        //             'monto_asignado' => $pivot->pivot->monto_asignado,
        //         ]);
        //         $total_propina_usuario += $pivot->pivot->monto_asignado;
        //     }
        // }


        foreach ($propinas as $propina) {
            $pivot = $propina->users()->where('users.id', $user->id)->first();
            if ($pivot) {
                $nombre_cliente = 'Desconocido';
        
                if ($propina->propinable) {
                    if ($propina->propinable_type == VentaDirecta::class) {
                        $nombre_cliente = 'Venta Directa';
                    } elseif ($propina->propinable_type == Consumo::class) {
                        $nombre_cliente = optional($propina->propinable->venta->reserva->cliente)->nombre_cliente ?? 'Desconocido';
                    }
                }
        
                $asignaciones->push((object)[
                    'nombre_cliente' => $nombre_cliente,
                    'monto_asignado' => $pivot->pivot->monto_asignado,
                ]);
        
                $total_propina_usuario += $pivot->pivot->monto_asignado;
            }
        }


        return view('themes.backoffice.pages.sueldo.detalle_diario', [
            'user' => $user,
            'fecha' => $fecha,
            'sueldo' => $sueldo,
            'asignaciones' => $asignaciones,
            'total_propina_usuario' => $total_propina_usuario,
        ]);
    }


    // public function view_maso(User $user, Request $request)
    // {
    //     $userId = $user->id;

    //     // Obtener mes y año del request o usar el mes y año actuales como predeterminado
    //     $currentMonth = $request->input('mes', now()->month);
    //     $currentYear = $request->input('anio', now()->year);

    //     // Filtrar registros por el mes seleccionado
    //     $sueldos = Sueldo::where('id_user', $userId)
    //         ->whereMonth('dia_trabajado', $currentMonth)
    //         ->whereYear('dia_trabajado', $currentYear)
    //         ->orderBy('dia_trabajado', 'asc')
    //         ->paginate(15); // Paginación con 10 registros por página

    //     // Verificar la autorización para al menos un sueldo
    //     if ($sueldos->isNotEmpty()) {
    //         $this->authorize('view', $sueldos->first());
    //     } else {
    //         abort(403);
    //     }

    //     return view('themes.backoffice.pages.sueldo.view_maso', [
    //         'sueldos' => $sueldos,
    //         'mes' => $currentMonth,
    //         'anio' => $currentYear,
    //         'user' => $user,
    //     ]);
    // }





    public function view_maso(User $user, Request $request)
    {
        $userId = $user->id;

        $currentMonth = $request->input('mes', now()->month);
        $currentYear  = $request->input('anio', now()->year);

        // Query base de sueldos del usuario
        $baseQuery = Sueldo::where('id_user', $userId)
            ->whereMonth('dia_trabajado', $currentMonth)
            ->whereYear('dia_trabajado', $currentYear);

        // Paginación por día
        $sueldos = (clone $baseQuery)
            ->orderBy('dia_trabajado', 'asc')
            ->paginate(15);

        if ($sueldos->isNotEmpty()) {
            $this->authorize('view', $sueldos->first());
        } else {
            abort(403);
        }

        // 🔹 Cantidad de masajes por cada día de sueldo (usando MASAJES.created_at)
        $masajesPorDia = [];
        foreach ($sueldos as $sueldo) {
            $fecha = Carbon::parse($sueldo->dia_trabajado)->toDateString();


            $masajesPorDia[$fecha] = DB::table('masajes as m')
                ->join('reservas as r', 'r.id', '=', 'm.id_reserva')
                ->where('m.user_id', $userId)
                ->whereDate('r.fecha_visita', $fecha)
                ->count();
        }

        // Total masajes del mes en plata
        $totalMasajesMes = (clone $baseQuery)->sum('sub_sueldo');

        // Bonos del mes desde sueldos_pagados
        $bonos = DB::table('sueldos_pagados')
            ->where('user_id', $userId)
            ->whereMonth('fecha_pago', $currentMonth)
            ->whereYear('fecha_pago', $currentYear)
            ->orderBy('fecha_pago')
            ->get();

        $totalBonosMes  = $bonos->sum('bono');
        $totalMesGlobal = $totalMasajesMes + $totalBonosMes;

        return view('themes.backoffice.pages.sueldo.view_maso', [
            'sueldos'         => $sueldos,
            'mes'             => $currentMonth,
            'anio'            => $currentYear,
            'user'            => $user,
            'masajesPorDia'   => $masajesPorDia, // ['2025-05-13' => 2, ...]
            'bonos'           => $bonos,
            'totalMasajesMes' => $totalMasajesMes,
            'totalBonosMes'   => $totalBonosMes,
            'totalMesGlobal'  => $totalMesGlobal,
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

        try {
            $sueldos = $request->input('sueldos');

            foreach ($sueldos as $sueldo) {
                // Actualiza si existe o crea un nuevo registro
                Sueldo::updateOrCreate(
                    [
                        'dia_trabajado' => $sueldo['dia_trabajado'],
                        'id_user' => $sueldo['id_user'],
                    ],
                    [
                        'valor_dia' => $sueldo['valor_dia'],
                        'sub_sueldo' => $sueldo['sub_sueldo'],
                        'total_pagar' => $sueldo['total_pagar'],
                    ]
                );
            }

            Alert::toast('Se almacenaron los sueldos correctamente', 'success')->toToast('center');
            return redirect()->back();

        } catch (Exception $e) {

            Alert::toast('No se almacenaron los sueldos ' . $e->getMessage(), 'error')->toToast('center');
            return redirect()->back()->withErrors($e->getMessage())->withInput();
        }

    }

    // public function actualizarSueldoBase(Request $request)
    // {
    //     $request->validate([
    //         'sueldoBase' => 'required|numeric',
    //     ]);

    //     // Recuperar el sueldo base actual del cache
    //     $sueldoActual = Cache::get('sueldoBase');


    //     // Verificar si el valor es diferente al actual
    //     if ($sueldoActual !== $request->sueldoBase) {
    //         // Guardar el nuevo valor en cache
    //         Cache::forever('sueldoBase', $request->sueldoBase);
    
    //         // Redirigir con un mensaje de éxito
    //         return redirect()->back()->with('success', 'El sueldo base se ha actualizado correctamente.');

    //     }else{
    //         // Redirigir con un mensaje indicando que no hubo cambios
    //         return redirect()->back()->with('info', 'El sueldo base es el mismo, no se realizaron cambios.');
    //     }

    // }

    public function store_maso(Request $request)
    {

        try {
            $sueldos = $request->input('sueldos');

            foreach ($sueldos as $sueldo) {
                // Actualiza si existe o crea un nuevo registro
                Sueldo::updateOrCreate(
                    [
                        'dia_trabajado' => $sueldo['dia_trabajado'],
                        'id_user' => $sueldo['id_user'],
                    ],
                    [
                        'valor_dia' => $sueldo['valor_dia'],
                        'sub_sueldo' => $sueldo['sub_sueldo'],
                        'total_pagar' => $sueldo['total_pagar'],
                    ]
                );
            }

            Alert::toast('Se almacenaron los sueldos correctamente', 'success')->toToast('top');
            return redirect()->back();

        } catch (Exception $e) {

            Alert::toast('No se almacenaron los sueldos ' . $e->getMessage(), 'error')->toToast('top');
            return redirect()->back()->withErrors($e->getMessage())->withInput();
        }

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
        //
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
