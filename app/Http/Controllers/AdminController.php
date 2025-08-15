<?php
namespace App\Http\Controllers;

use App\Asignacion;
use App\Asistencia;
use App\Cliente;
use App\Consumo;
use App\GiftCard;
use App\Insumo;
use App\Masaje;
use App\PoroPoro;
use App\PoroPoroVenta;
use App\Programa;
use App\Propina;
use App\Reserva;
use App\Role;
use App\Sueldo;
use App\TipoTransaccion;
use App\User;
use App\Venta;
use App\VentaDirecta;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{

    public function __construct()
    {
        // $this->middleware('role:' . config('app.admin_role') . '-' . config('app.anfitriona_role') . '-' . config('app.cocina_role') . '-' . config('app.garzon_role') . '-' . config('app.masoterapeuta_role') . '-' . config('app.barman_role') . '-' . config('app.jefe_local_role'));

        $this->middleware('role:' . implode('|', [
            config('app.admin_role'),
            config('app.administracion_role'),
            config('app.aseo_role'),
            config('app.anfitriona_role'),
            config('app.barman_role'),
            config('app.cocina_role'),
            config('app.garzon_role'),
            config('app.informatica_role'),
            config('app.jefe_local_role'),
            config('app.masoterapeuta_role'),
            config('app.mantencion_role'),
        ]));

    }

    public function show()
    {

        $hoy      = Carbon::today();
        $reservas = Reserva::all();
        $ventas   = Venta::all();

        // Contar el número total de clientes
        $totalClientes = Cliente::count();

        // Contar el número total de reservas
        $totalReservas = Reserva::count();

        // $totalAsistentesDia = $reservas->sum('cantidad_personas');
        $totalAsistentesDia = Reserva::where('fecha_visita', $hoy)->sum('cantidad_personas');

        $totalConsumos = Consumo::count();

        $cantidadFuncionarios = User::count();
        $cantidadRoles        = Role::count();

        $insumosCriticos = Insumo::whereColumn('cantidad', '<=', 'stock_critico')->get();

        $masajesAsignados = Masaje::count();

        $poroporo = PoroPoro::count();

        $user = auth()->user();

        $inicioSemana = Carbon::now()->startOfWeek(); // Por defecto, inicia el lunes
        $finSemana    = Carbon::now()->endOfWeek();   // Termina el domingo

        // Consulta para contar las asignaciones que caen dentro de la semana actual
        $asignacionesSemanaActual = Asignacion::whereBetween('fecha', [$inicioSemana, $finSemana])->count();

        $asistenciaHoy = Asistencia::whereDate('fecha', Carbon::today())->with('users')->first();

        $asistentesConteo = $asistenciaHoy ? $asistenciaHoy->users->count() : 0;

        $sueldosMes = Sueldo::whereMonth("dia_trabajado", Carbon::now()->month);

        if ($user->has_role(config('app.admin_role'))) {

            return view('themes.backoffice.pages.admin.show', compact('totalClientes', 'totalReservas', 'insumosCriticos', 'masajesAsignados', 'asignacionesSemanaActual', 'totalConsumos', 'sueldosMes', 'totalAsistentesDia', 'cantidadFuncionarios', 'cantidadRoles', 'asistentesConteo', 'poroporo'));
        }

        if ($user->has_role(config('app.anfitriona_role')) || $user->has_role(config('app.jefe_local_role'))) {
            return view('themes.backoffice.pages.admin.show', compact('totalAsistentesDia', 'totalClientes', 'totalReservas', 'insumosCriticos', 'masajesAsignados', 'asignacionesSemanaActual', 'totalConsumos', 'sueldosMes'));
        }

        if ($user->has_role(config('app.cocina_role')) || $user->has_role(config('app.garzon_role'))) {
            return redirect()->action([MenuController::class, 'index']);
        }

        if ($user->has_role(config('app.masoterapeuta_role'))) {
            return redirect()->action([MasajeController::class, 'index']);
        }

        if ($user->has_role(config('app.barman_role'))) {
            return redirect()->action([BarmanController::class, 'index']);
        }
    }

    public function index()
    {
        Carbon::setLocale('es');
        $masajes = Masaje::with('users', 'reservas');

        // Obtener usuarios con el rol de masoterapeuta
        $masoterapeutas = User::whereHas('roles', function ($query) {
            $query->where('name', 'masoterapeuta');
        })->get();

        // // Configurar la semana para que comience el lunes
        // Carbon::setWeekStartsAt(Carbon::MONDAY);
        // Carbon::setWeekEndsAt(Carbon::SUNDAY);

        // Obtener la cantidad de masajes realizados por cada masoterapeuta en la semana en curso
        $inicioSemana = Carbon::now()->startOfWeek();
        $finSemana    = Carbon::now()->endOfWeek();

        // Definir los días de la semana en español
        $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        // Calcular las fechas de cada día de la semana
        $fechasDiasSemana = [];
        foreach ($diasSemana as $indice => $dia) {
            $fechasDiasSemana[$dia] = $inicioSemana->copy()->addDays($indice)->toDateString();
        }

        // Crear un array para almacenar la cantidad de masajes por semana y por día por masoterapeuta
        $cantidadMasajesPorSemana = [];
        $cantidadMasajesPorDia    = [];

        // foreach ($masoterapeutas as $masoterapeuta) {
        //     // Total de masajes de la semana (por fecha de reserva)
        //     $cantidadMasajesPorSemana[$masoterapeuta->id] = Masaje::where('user_id', $masoterapeuta->id)
        //         ->whereHas('reserva', function ($query) use ($inicioSemana, $finSemana) {
        //             $query->whereBetween('fecha_visita', [$inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d')]);
        //         })
        //         ->count();

        //     // Masajes por día según la fecha de la reserva
        //     $masajesPorDia = Masaje::where('user_id', $masoterapeuta->id)
        //         ->whereHas('reserva', function ($query) use ($inicioSemana, $finSemana) {
        //             $query->whereBetween('fecha_visita', [$inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d')]);
        //         })
        //         ->with('reserva') // para evitar múltiples consultas al acceder a la fecha
        //         ->get()
        //         ->groupBy(function ($masaje) {
        //             return Carbon::parse($masaje->reserva->fecha_visita)->format('N');
        //         });

        //     // Inicializa los días de la semana
        //     $cantidadMasajesPorDia[$masoterapeuta->id] = [];
        //     foreach ($diasSemana as $indice => $dia) {
        //         $diaNumero                                       = $indice + 1;
        //         $cantidadMasajesPorDia[$masoterapeuta->id][$dia] = isset($masajesPorDia[$diaNumero])
        //         ? $masajesPorDia[$diaNumero]->count()
        //         : 0;
        //     }
        // }

        foreach ($masoterapeutas as $masoterapeuta) {
            // Masajes de la semana con detalles
            $masajesSemana = Masaje::where('user_id', $masoterapeuta->id)
                ->whereHas('reserva', function ($query) use ($inicioSemana, $finSemana) {
                    $query->whereBetween('fecha_visita', [$inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d')]);
                })
                ->with('reserva')
                ->get();

            // Cálculo total semana
            $cantidadMasajesPorSemana[$masoterapeuta->id] = $masajesSemana->count();

            // Inicializa por día
            $cantidadMasajesPorDia[$masoterapeuta->id] = [];
            foreach ($diasSemana as $indice => $dia) {
                $diaNumero = $indice + 1;

                // Filtrar masajes del día
                $masajesDia = $masajesSemana->filter(function ($masaje) use ($diaNumero) {
                    return Carbon::parse($masaje->reserva->fecha_visita)->format('N') == $diaNumero;
                });

                // Separar normales y con tiempo extra
                $normales = $masajesDia->where('tiempo_extra', false)->count();
                $extras   = $masajesDia->where('tiempo_extra', true)->count();

                // Calcular sueldo diario (extra vale el doble)
                $totalDia = ($normales * $masoterapeuta->salario) + ($extras * ($masoterapeuta->salario * 2));

                $cantidadMasajesPorDia[$masoterapeuta->id][$dia] = [
                    'normales' => $normales,
                    'extras'   => $extras,
                    'total'    => $totalDia,
                ];
            }
            $totalSemanal = 0;
            foreach ($diasSemana as $indice => $dia) {
                $dataDia = $cantidadMasajesPorDia[$masoterapeuta->id][$dia];
                $totalSemanal += $dataDia['total'];
            }
            $totalSemanas[$masoterapeuta->id] = $totalSemanal;
        }


        return view('themes.backoffice.pages.admin.index', [
            'masoterapeutas'           => $masoterapeutas,
            'cantidadMasajesPorSemana' => $cantidadMasajesPorSemana,
            'cantidadMasajesPorDia'    => $cantidadMasajesPorDia,
            'diasSemana'               => $diasSemana,

            'fechasDiasSemana'         => $fechasDiasSemana,
            'totalSemanas'             => $totalSemanas
        ]);
    }

    public function team()
    {
        Carbon::setLocale('es');
        // Obtener usuarios con roles especificos
        $usuarios = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['anfitriona', 'barman', 'cocina', 'garzon', 'jefe local']);
        })->get();

        $inicioSemana = Carbon::now()->startOfWeek();
        $finSemana    = Carbon::now()->endOfWeek();

        // Definir los días de la semana en español
        $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        // Obtener todas las asignaciones con usuarios, filtrando por la semana y agrupando por nombre del día
        $asignacionesPorDia = Asignacion::with('users')
            ->whereBetween('fecha', [$inicioSemana, $finSemana])
            ->get()
            ->groupBy(function ($asignacion) {
                return Carbon::parse($asignacion->fecha)->locale('es')->isoFormat('dddd');
            });

        // Convertir la primera letra de cada día a mayúsculas para que coincida con el array $diasSemana
        $asignacionesPorDia = $asignacionesPorDia->mapWithKeys(function ($value, $key) {
            return [ucfirst($key) => $value];
        });

        // Obtener los detalles de consumo junto con la fecha de la visita relacionada
        $detallesPorFecha = DB::table('propinas')
            ->selectRaw('fecha, SUM(cantidad) as total_subtotal')
            ->whereBetween('fecha', [$inicioSemana, $finSemana])
            ->groupBy('fecha')
            ->get();

        // Crear un array para almacenar las propinas por día y dividirlas entre los usuarios asignados
        $propinasPorDia      = [];
        $totalPropinasSemana = 0;
        $diaTrabajado        = null;

        foreach ($detallesPorFecha as $detalle) {
            $fecha        = Carbon::parse($detalle->fecha)->locale('es')->isoFormat('dddd');
            $fecha        = ucfirst($fecha); // Convertir la primera letra a mayúscula para coincidir
            $diaTrabajado = Carbon::parse($detalle->fecha)->format('Y-m-d');
            if (isset($asignacionesPorDia[$fecha])) {
                $totalUsuarios     = $asignacionesPorDia[$fecha]->pluck('users')->flatten()->count();
                $propinaPorUsuario = $totalUsuarios > 0 ? ($detalle->total_subtotal / $totalUsuarios) : 0;

                // $propinasPorDia[$fecha] = $propinaPorUsuario;
                $propinasPorDia[$fecha] = ['propina' => $propinaPorUsuario, 'dia_trabajado' => $diaTrabajado];

                $totalPropinasSemana += $detalle->total_subtotal;

            } else {
                // $propinasPorDia[$fecha] = 0;
                $propinasPorDia[$fecha] = ['propina' => 0,
                    'dia_trabajado'                      => $diaTrabajado];
            }
        }

        $totalPorUsuario = [];

        foreach ($usuarios as $usuario) {
            $totalDiasAsignados = 0;
            $propinaUsuario     = 0;

            foreach ($diasSemana as $dia) {
                if (isset($asignacionesPorDia[$dia])) {
                    $usuariosDia = $asignacionesPorDia[$dia]->pluck('users')->flatten();

                    if ($usuariosDia->contains('id', $usuario->id)) {
                        $totalDiasAsignados++;
                        $propinaUsuario += $propinasPorDia[$dia]['propina'] ?? 0;
                    }
                }
            }

            // Obtener el sueldo base individual del usuario (si no tiene, usar 45000 por defecto)
            $sueldoBase = $usuario->salario;

            $totalPorUsuario[$usuario->name] = ($totalDiasAsignados * $sueldoBase) + $propinaUsuario;
        }

        $totalSueldoGeneral = array_sum($totalPorUsuario);

        // Calcular las fechas exactas de los días de la semana
        $fechasSemana = [];
        for ($i = 0; $i < 7; $i++) {
            $fechasSemana[$diasSemana[$i]] = $inicioSemana->copy()->addDays($i)->format('Y-m-d');
        }

        return view('themes.backoffice.pages.admin.team', [
            'diasSemana'         => $diasSemana,
            'asignacionesPorDia' => $asignacionesPorDia,
            'propinasPorDia'     => $propinasPorDia,
            'totalPorUsuario'    => $totalPorUsuario,
            'totalSueldos'       => $totalSueldoGeneral,
            'usuarios'           => $usuarios,
            'diaT'               => $diaTrabajado,
            'fechasSemana'       => $fechasSemana,
        ]);
    }

    // public function team()
    // {
    //     Carbon::setLocale('es');

    //     // Obtener usuarios con roles específicos
    //     $usuarios = User::whereHas('roles', function ($query) {
    //         $query->whereIn('name', ['anfitriona', 'barman', 'cocina', 'garzon', 'jefe local']);
    //     })->get();

    //     $inicioSemana = Carbon::now()->startOfWeek();
    //     $finSemana = Carbon::now()->endOfWeek();

    //     // Días de la semana
    //     $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

    //     // Obtener asignaciones con usuarios, filtradas por semana y agrupadas por nombre del día
    //     $asignacionesPorDia = Asignacion::with('users')
    //         ->whereBetween('fecha', [$inicioSemana, $finSemana])
    //         ->get()
    //         ->groupBy(function ($asignacion) {
    //             return Carbon::parse($asignacion->fecha)->locale('es')->isoFormat('dddd');
    //         });

    //     // Asegurar que la primera letra esté en mayúscula
    //     $asignacionesPorDia = $asignacionesPorDia->mapWithKeys(function ($value, $key) {
    //         return [ucfirst($key) => $value];
    //     });

    //     // Obtener todas las propinas de la semana
    //     $propinas = Propina::with('propinable')
    //         ->whereBetween('fecha', [$inicioSemana, $finSemana])
    //         ->get();

    //     // Arreglos para propinas por día y usuario
    //     $propinasPorDia = [];
    //     $propinasPorUsuarioYFecha = [];

    //     $totalPropinasSemana = 0;
    //     $diaTrabajado = null;

    //     foreach ($propinas as $propina) {
    //         $fecha = Carbon::parse($propina->fecha)->format('Y-m-d');
    //         $diaSemana = ucfirst(Carbon::parse($propina->fecha)->locale('es')->isoFormat('dddd'));

    //         $diaTrabajado = $fecha; // última fecha procesada

    //         // Buscamos usuarios asignados ese día
    //         $usuariosAsignados = $asignacionesPorDia[$diaSemana] ?? collect();

    //         if ($usuariosAsignados->isEmpty()) {
    //             // No hay usuarios asignados este día
    //             continue;
    //         }

    //         $usuariosDelDia = $usuariosAsignados->pluck('users')->flatten();
    //         $totalUsuarios = $usuariosDelDia->count();

    //         if ($totalUsuarios > 0) {
    //             $montoPorUsuario = $propina->cantidad / $totalUsuarios;

    //             foreach ($usuariosDelDia as $usuario) {
    //                 $propinasPorUsuarioYFecha[$fecha][$usuario->id] = 
    //                     ($propinasPorUsuarioYFecha[$fecha][$usuario->id] ?? 0) + $montoPorUsuario;
    //             }

    //             // Para mostrar la propina general del día (lo que tu vista espera en propinasPorDia)
    //             if (!isset($propinasPorDia[$diaSemana])) {
    //                 $propinasPorDia[$diaSemana] = [
    //                     'propina' => 0,
    //                     'dia_trabajado' => $fecha
    //                 ];
    //             }
    //             $propinasPorDia[$diaSemana]['propina'] += $propina->cantidad;
    //             $totalPropinasSemana += $propina->cantidad;
    //         } else {
    //             // Nadie asignado, pero registramos 0 para la vista
    //             $propinasPorDia[$diaSemana] = [
    //                 'propina' => 0,
    //                 'dia_trabajado' => $fecha
    //             ];
    //         }
    //     }

    //     // Calcular total de propinas por usuario
    //     $totalPropinasUsuario = [];

    //     foreach ($propinasPorUsuarioYFecha as $fecha => $usuarios) {
    //         foreach ($usuarios as $userId => $monto) {
    //             $totalPropinasUsuario[$userId] = ($totalPropinasUsuario[$userId] ?? 0) + $monto;
    //         }
    //     }

    //     // Sueldo base
    //     $pagoBasePorUsuario = Cache::get('sueldoBase') ?? 45000;

    //     // Calcular el total a pagar por usuario
    //     $totalPorUsuario = [];

    //     foreach ($usuarios as $usuario) {
    //         $totalDiasAsignados = 0;

    //         foreach ($diasSemana as $dia) {
    //             if (isset($asignacionesPorDia[$dia])) {
    //                 $usuariosDia = $asignacionesPorDia[$dia]->pluck('users')->flatten();

    //                 if ($usuariosDia->contains('id', $usuario->id)) {
    //                     $totalDiasAsignados++;
    //                 }
    //             }
    //         }

    //         $propinaUsuario = $totalPropinasUsuario[$usuario->id] ?? 0;

    //         $totalPorUsuario[$usuario->name] = ($totalDiasAsignados * $pagoBasePorUsuario) + $propinaUsuario;
    //     }

    //     $totalSueldoGeneral = array_sum($totalPorUsuario);

    //     // Fechas de cada día de la semana
    //     $fechasSemana = [];
    //     for ($i = 0; $i < 7; $i++) {
    //         $fechasSemana[$diasSemana[$i]] = $inicioSemana->copy()->addDays($i)->format('Y-m-d');
    //     }

    //     dd($diasSemana,
    //     $asignacionesPorDia,
    //     $propinasPorDia,
    //     $totalPorUsuario,
    //     $totalSueldoGeneral,
    //     $usuarios,
    //     $diaTrabajado,
    //     $pagoBasePorUsuario,
    //     $fechasSemana);

    //     return view('themes.backoffice.pages.admin.team', [
    //         'diasSemana' => $diasSemana,
    //         'asignacionesPorDia' => $asignacionesPorDia,
    //         'propinasPorDia' => $propinasPorDia,
    //         'totalPorUsuario' => $totalPorUsuario,
    //         'totalSueldos' => $totalSueldoGeneral,
    //         'usuarios' => $usuarios,
    //         'diaT' => $diaTrabajado,
    //         'base' => $pagoBasePorUsuario,
    //         'fechasSemana' => $fechasSemana,
    //     ]);
    // }

    public function ingresos()
    {
        $estadoMensual = DB::table('ventas')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('
            YEAR(reservas.fecha_visita) AS anio,
            MONTH(reservas.fecha_visita) AS mes,
            COUNT(*) AS total_ventas,
            SUM(ventas.abono_programa) AS total_abonos,
            SUM(ventas.total_pagar) AS por_pagar
        ')
            ->groupBy(DB::raw('YEAR(reservas.fecha_visita)'), DB::raw('MONTH(reservas.fecha_visita)'))
            ->orderByDesc(DB::raw('YEAR(reservas.fecha_visita)'))
            ->orderByDesc(DB::raw('MONTH(reservas.fecha_visita)'))
            ->paginate(12);

        foreach ($estadoMensual as $venta) {
            // Consumos y servicios extra
            $venta->consumo = Venta::whereHas('reserva', function ($query) use ($venta) {
                $query->whereYear('fecha_visita', $venta->anio)
                    ->whereMonth('fecha_visita', $venta->mes);
            })->with('consumo.detallesConsumos', 'consumo.detalleServiciosExtra')
                ->get()
                ->pluck('consumo')
                ->filter();

            // Total de GiftCards creadas ese mes (todas)
            $venta->giftcards = DB::table('gift_cards')
                ->whereYear('created_at', $venta->anio)
                ->whereMonth('created_at', $venta->mes)
                ->sum('monto');
        }

        return view('themes.backoffice.pages.admin.finanzas.ingresos', [
            'estadoMensual' => $estadoMensual,
        ]);
    }

    public function detalleMes($anio, $mes)
    {
        //* 1. Ventas (sin GiftCard para evitar doble conteo)
        $ventas = Venta::select(
            DB::raw('DATE(reservas.fecha_visita) as fecha'),
            DB::raw('DAY(reservas.fecha_visita) AS dia'),
            DB::raw('SUM(ventas.abono_programa) as abono'),
            DB::raw('SUM(ventas.diferencia_programa) as diferencia'),
            DB::raw('SUM(ventas.total_pagar) as pendiente'),
            DB::raw('SUM(programas.valor_programa * reservas.cantidad_personas) as total_pagar')
        )
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->join('programas', 'reservas.id_programa', '=', 'programas.id')
            ->whereNotIn('ventas.id', function ($sub) {
                $sub->select('id_venta')->from('gift_cards')->whereNotNull('id_venta');
            })
            ->whereMonth('reservas.fecha_visita', $mes)
            ->whereYear('reservas.fecha_visita', $anio)
            ->groupBy(DB::raw('DATE(reservas.fecha_visita)'), DB::raw('DAY(reservas.fecha_visita)'))
            ->orderBy('fecha', 'desc')
            ->get();

        //* 2. GiftCards agrupadas por fecha de creación
        $giftcards = DB::table('gift_cards')
            ->select(
                DB::raw('DATE(created_at) as fecha'),
                DB::raw('DAY(created_at) as dia'),
                DB::raw('SUM(monto) as monto_gc')
            )
            ->whereYear('created_at', $anio)
            ->whereMonth('created_at', $mes)
            ->groupBy(DB::raw('DATE(created_at)'), DB::raw('DAY(created_at)'))
            ->get();

        //* 3. Combinar ventas y GiftCards por fecha
        $fechasUnicas = $ventas->pluck('fecha')
            ->merge($giftcards->pluck('fecha'))
            ->unique()
            ->sortByDesc(function ($fecha) {return $fecha;});

        $merged = collect();

        foreach ($fechasUnicas as $fecha) {
            $ventaDia = $ventas->firstWhere('fecha', $fecha);
            $giftDia  = $giftcards->firstWhere('fecha', $fecha);

            $merged->push((object) [
                'fecha'           => $fecha,
                'dia'             => \Carbon\Carbon::parse($fecha)->day,
                'abono'           => $ventaDia->abono ?? 0,
                'diferencia'      => $ventaDia->diferencia ?? 0,
                'pendiente'       => $ventaDia->pendiente ?? 0,
                'total_pagar'     => $ventaDia->total_pagar ?? 0,
                'monto_giftcards' => $giftDia->monto_gc ?? 0,
            ]);
        }

        //* 4. Paginación
        $perPage      = 10;
        $currentPage  = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $merged->slice(($currentPage - 1) * $perPage)->values();

        $ventasAgrupadas = new LengthAwarePaginator(
            $currentItems,
            $merged->count(),
            $perPage,
            $currentPage,
            [
                'path'  => request()->url(),
                'query' => request()->query(),
            ]
        );

        //* 5. Consumos y servicios extra para días con ventas
        foreach ($ventasAgrupadas as $venta) {
            $venta->consumo = Venta::whereHas('reserva', function ($query) use ($venta) {
                $query->whereDate('fecha_visita', $venta->fecha);
            })
                ->with('consumo.detallesConsumos', 'consumo.detalleServiciosExtra')
                ->get()
                ->pluck('consumo')
                ->filter();
        }

        //* 6. Sumar totales para resumen
        $ingresosVentas   = $ventas->sum('abono');
        $ventasPendientes = $ventas->sum('pendiente');

        //* 7. Resumen por tipo de transacción (igual que antes)
        $tiposTransacciones = TipoTransaccion::all()->map(function ($tipo) use ($anio, $mes) {
            $abono = Venta::where('id_tipo_transaccion_abono', $tipo->id)
                ->whereHas('reserva', function ($query) use ($anio, $mes) {
                    $query->whereYear('fecha_visita', $anio)
                        ->whereMonth('fecha_visita', $mes);
                })
                ->whereNotIn('id', function ($sub) {
                    $sub->select('id_venta')->from('gift_cards')->whereNotNull('id_venta');
                })
                ->sum('abono_programa');

            $total_pago1 = \App\PagoConsumo::where('id_tipo_transaccion1', $tipo->id)
                ->whereHas('venta.reserva', function ($query) use ($anio, $mes) {
                    $query->whereYear('fecha_visita', $anio)
                        ->whereMonth('fecha_visita', $mes);
                })
                ->sum('pago1');

            $total_pago2 = \App\PagoConsumo::where('id_tipo_transaccion2', $tipo->id)
                ->whereNotNull('pago2')
                ->whereHas('venta.reserva', function ($query) use ($anio, $mes) {
                    $query->whereYear('fecha_visita', $anio)
                        ->whereMonth('fecha_visita', $mes);
                })
                ->sum('pago2');

            $ventaDirecta = VentaDirecta::where('id_tipo_transaccion', $tipo->id)
                ->whereYear('fecha', $anio)
                ->whereMonth('fecha', $mes)
                ->sum('subtotal');

            $tipo->total_abonos      = $abono;
            $tipo->total_diferencias = $total_pago1 + $total_pago2;
            $tipo->venta_directa     = $ventaDirecta;

            return $tipo;
        });

        //* 8. Programas (igual que antes)
        $programas = Programa::with(['reservas' => function ($query) use ($mes, $anio) {
            $query->whereMonth('fecha_visita', $mes)
                ->whereYear('fecha_visita', $anio);
        }])->get()->map(function ($programa) {
            $programa->total_programas = $programa->reservas->count();
            return $programa;
        });

        //* 9. Nombre del mes
        $nombreMes = Carbon::createFromDate($anio, $mes, 1)
            ->locale('es')->translatedFormat('F Y');

        return view('themes.backoffice.pages.admin.finanzas.detalle-mes', compact(
            'ventasAgrupadas', 'nombreMes', 'anio', 'mes',
            'ingresosVentas', 'ventasPendientes', 'tiposTransacciones', 'programas'
        ));
    }

    public function ingresosDiarios($anio, $mes, $dia)
    {
        $ingresosVentas   = 0;
        $ventasPendientes = 0;
        $totalGc          = 0;

        $gcs = GiftCard::whereYear('created_at', $anio)
            ->whereMonth('created_at', $mes)
            ->whereDay('created_at', $dia)
            ->get();

        foreach ($gcs as $gc) {
            $totalGc += $gc->monto;
        }

        $cantidadGc = COUNT($gcs) ?? 0;

        $ventas = Venta::whereHas('reserva', function ($query) use ($mes, $anio, $dia) {
            $query->whereMonth('fecha_visita', $mes)
                ->whereYear('fecha_visita', $anio)
                ->whereDay('fecha_visita', $dia);
        })->with('reserva.cliente', 'reserva.programa', 'consumo.detallesConsumos', 'consumo.detalleServiciosExtra')->paginate(20);

        // Marcar si cada venta fue pagada con GiftCard
        foreach ($ventas as $venta) {
            $venta->pagado_con_giftcard = GiftCard::where('id_venta', $venta->id)->exists();
            // Evitar sumar abono/diferencia si es con giftcard
            if (! $venta->pagado_con_giftcard) {
                $ingresosVentas += $venta->abono_programa;
                $ingresosVentas += $venta->diferencia_programa;
                $ventasPendientes += $venta->total_pagar;
            }
        }

        $tiposTransacciones = TipoTransaccion::all()->map(function ($tipo) use ($anio, $mes, $dia) {
            $abono = Venta::where('id_tipo_transaccion_abono', $tipo->id)
                ->whereHas('reserva', function ($query) use ($anio, $mes, $dia) {
                    $query->whereYear('fecha_visita', $anio)
                        ->whereMonth('fecha_visita', $mes)
                        ->whereDay('fecha_visita', $dia);
                })
                ->sum('abono_programa');

            $total_pago1 = \App\PagoConsumo::where('id_tipo_transaccion1', $tipo->id)
                ->whereHas('venta.reserva', function ($query) use ($anio, $mes, $dia) {
                    $query->whereYear('fecha_visita', $anio)
                        ->whereMonth('fecha_visita', $mes)
                        ->whereDay('fecha_visita', $dia);
                })
                ->sum('pago1');

            $total_pago2 = \App\PagoConsumo::where('id_tipo_transaccion2', $tipo->id)
                ->whereNotNull('pago2')
                ->whereHas('venta.reserva', function ($query) use ($anio, $mes, $dia) {
                    $query->whereYear('fecha_visita', $anio)
                        ->whereMonth('fecha_visita', $mes)
                        ->whereDay('fecha_visita', $dia);
                })
                ->sum('pago2');

            $ventaDirecta = VentaDirecta::where('id_tipo_transaccion', $tipo->id)
                ->whereYear('fecha', $anio)
                ->whereMonth('fecha', $mes)
                ->whereDay('fecha', $dia)
                ->sum('subtotal');

            $poroPoro = PoroPoroVenta::where('id_tipo_transaccion', $tipo->id)
                ->whereYear('fecha', $anio)
                ->whereMonth('fecha', $mes)
                ->whereDay('fecha', $dia)
                ->sum('total');

            $tipo->total_abonos      = $abono;
            $tipo->total_diferencias = $total_pago1 + $total_pago2;
            $tipo->venta_directa     = $ventaDirecta;
            $tipo->poro              = $poroPoro;

            return $tipo;
        });

        $programas = Programa::all()->map(function ($programa) use ($dia, $mes, $anio) {
            $cuenta = Reserva::where('id_programa', $programa->id)
                ->whereDay('fecha_visita', $dia)
                ->whereMonth('fecha_visita', $mes)
                ->whereYear('fecha_visita', $anio)
                ->count();

            $programa->total_programas = $cuenta;
            return $programa;
        });

        $propinasVentaDirecta = VentaDirecta::whereDay('fecha', $dia)
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->get();

        $nombreMes = Carbon::createFromDate($anio, $mes, $dia)
            ->locale('es')
            ->translatedFormat('d \d\e F \d\e Y');

        return view('themes.backoffice.pages.admin.finanzas.detalle-dia', compact(
            'ventas',
            'nombreMes',
            'anio',
            'mes',
            'dia',
            'ingresosVentas',
            'ventasPendientes',
            'tiposTransacciones',
            'programas',
            'propinasVentaDirecta',
            'totalGc',
            'cantidadGc',
        ));
    }

    public function consumos()
    {
        $consumoMensual = DB::table('consumos')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->join('detalles_consumos', 'detalles_consumos.id_consumo', '=', 'consumos.id')
            ->selectRaw('
            YEAR(reservas.fecha_visita) AS anio,
            MONTH(reservas.fecha_visita) AS mes,
            COUNT(*) AS total_consumos,
            SUM(detalles_consumos.subtotal) AS subtotales
        ')
            ->groupBy(DB::raw('YEAR(reservas.fecha_visita)'), DB::raw('MONTH(reservas.fecha_visita)'))
            ->orderByDesc(DB::raw('YEAR(reservas.fecha_visita)'))
            ->orderByDesc(DB::raw('MONTH(reservas.fecha_visita)'))
            ->paginate(12); // 12 meses por página

        $servicioMensual = DB::table('consumos')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->join('detalle_servicios_extra', 'detalle_servicios_extra.id_consumo', '=', 'consumos.id')
            ->selectRaw('
            YEAR(reservas.fecha_visita) AS anio,
            MONTH(reservas.fecha_visita) AS mes,
            COUNT(*) AS total_servicios,
            SUM(detalle_servicios_extra.subtotal) AS subtotales
        ')
            ->groupBy(DB::raw('YEAR(reservas.fecha_visita)'), DB::raw('MONTH(reservas.fecha_visita)'))
            ->orderByDesc(DB::raw('YEAR(reservas.fecha_visita)'))
            ->orderByDesc(DB::raw('MONTH(reservas.fecha_visita)'))
            ->paginate(12); // 12 meses por página

        return view('themes.backoffice.pages.admin.consumos.mensuales', compact('consumoMensual', 'servicioMensual'));
    }

    public function consumosMensuales($anio, $mes)
    {

        $ventas = Venta::with(['consumo.detallesConsumos.producto'])->whereHas('reserva', function ($query) use ($mes, $anio) {
            $query->whereMonth('fecha_visita', $mes)
                ->whereYear('fecha_visita', $anio);
        })->get();

        return view('themes.backoffice.pages.admin.consumos.detalle', compact('ventas'));
    }

    public function serviciosMensuales($anio, $mes)
    {

        $ventas = Venta::with(['consumo.detalleServiciosExtra.servicio'])->whereHas('reserva', function ($query) use ($mes, $anio) {
            $query->whereMonth('fecha_visita', $mes)
                ->whereYear('fecha_visita', $anio);
        })->get();

        return view('themes.backoffice.pages.admin.servicios.detalle', compact('ventas'));
    }

    // Role Anfitriona

    public function cierreCaja($anio, $mes, $dia)
    {
        $ingresosVentas   = 0;
        $ventasPendientes = 0;

        $ventas = Venta::whereHas('reserva', function ($query) use ($mes, $anio, $dia) {
            $query->whereMonth('fecha_visita', $mes)
                ->whereYear('fecha_visita', $anio)
                ->whereDay('fecha_visita', $dia);
        })->with('consumo.propina.users')->paginate(20);

        $tiposTransacciones = TipoTransaccion::all()->map(function ($tipo) use ($anio, $mes, $dia) {
            // Suma de pagos del campo pago1 con tipo 1
            $total_pago1 = \App\PagoConsumo::where('id_tipo_transaccion1', $tipo->id)
                ->whereHas('venta.reserva', function ($query) use ($anio, $mes, $dia) {
                    $query->whereYear('fecha_visita', $anio)
                        ->whereMonth('fecha_visita', $mes)
                        ->whereDay('fecha_visita', $dia);
                })
                ->sum('pago1');

            // Suma de pagos del campo pago2 con tipo 2 (solo si no es null)
            $total_pago2 = \App\PagoConsumo::where('id_tipo_transaccion2', $tipo->id)
                ->whereNotNull('pago2')
                ->whereHas('venta.reserva', function ($query) use ($anio, $mes, $dia) {
                    $query->whereYear('fecha_visita', $anio)
                        ->whereMonth('fecha_visita', $mes)
                        ->whereDay('fecha_visita', $dia);
                })
                ->sum('pago2');

            $ventaDirecta = VentaDirecta::where('id_tipo_transaccion', $tipo->id)
                ->whereYear('fecha', $anio)
                ->whereMonth('fecha', $mes)
                ->whereDay('fecha', $dia)
                ->sum('subtotal');

            $poroporo = PoroPoroVenta::where('id_tipo_transaccion', $tipo->id)
                ->whereYear('fecha', $anio)
                ->whereMonth('fecha', $mes)
                ->whereDay('fecha', $dia)
                ->sum('total');

            $tipo->total_diferencias = $total_pago1 + $total_pago2;
            $tipo->venta_directa     = $ventaDirecta;
            $tipo->poro_poro         = $poroporo;
            return $tipo;
        });

        $programas = Programa::all()->map(function ($programa) use ($dia, $mes, $anio) {
            $cuenta = Reserva::where('id_programa', $programa->id)
                ->whereHas('programa', function ($query) use ($dia, $mes, $anio) {
                    $query->whereDay('fecha_visita', $dia)
                        ->whereMonth('fecha_visita', $mes)
                        ->whereYear('fecha_visita', $anio);
                })
                ->count();

            $programa->total_programas = $cuenta;

            return $programa;
        });

        foreach ($ventas as $venta) {
            $ingresosVentas += $venta->abono_programa;
            $ingresosVentas += $venta->diferencia_programa;
            $ventasPendientes += $venta->total_pagar;
        }

        $propinas = Propina::whereHasMorph('propinable', [Consumo::class, VentaDirecta::class], function ($query, $type) use ($dia, $mes, $anio) {
            if ($type === Consumo::class) {
                $query->whereHas('venta.reserva', function ($q) use ($dia, $mes, $anio) {
                    $q->whereDay('fecha_visita', $dia)
                        ->whereMonth('fecha_visita', $mes)
                        ->whereYear('fecha_visita', $anio);
                });
            }
            if ($type === VentaDirecta::class) {
                $query->whereDay('fecha', $dia)
                    ->whereMonth('fecha', $mes)
                    ->whereYear('fecha', $anio);
            }
        })
            ->with('users')
            ->get();

        $propinasVentaDirecta = VentaDirecta::whereDay('fecha', $dia)
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->get();

        $totalPropina = $propinas->sum('cantidad');

        $usuariosUnicos = collect();

        foreach ($propinas as $propina) {
            foreach ($propina->users as $user) {
                $usuariosUnicos->put($user->id, $user);
            }
        }

        $cantidadUsuarios  = $usuariosUnicos->count();
        $propinaPorUsuario = $cantidadUsuarios > 0 ? ($totalPropina / $cantidadUsuarios) : 0;

        $nombreMes = Carbon::createFromDate($anio, $mes, $dia)
            ->locale('es')
            ->translatedFormat('d \d\e F \d\e Y');

        return view('themes.backoffice.pages.admin.anfitriona.cierre_caja', compact('ventas', 'nombreMes', 'anio', 'mes', 'dia', 'ingresosVentas', 'ventasPendientes', 'tiposTransacciones', 'programas', 'totalPropina', 'cantidadUsuarios', 'propinaPorUsuario', 'usuariosUnicos', 'propinasVentaDirecta', 'propinas'));

    }

    // Menu Movil
    public function menuMovil()
    {
        return view('themes.backoffice.pages.admin.moviles.admin');
    }
}
