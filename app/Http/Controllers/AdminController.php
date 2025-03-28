<?php

namespace App\Http\Controllers;

use App\Asignacion;
use App\Cliente;
use App\Consumo;
use App\Insumo;
use App\Masaje;
use App\Reserva;
use App\TipoTransaccion;
use App\User;
use App\Venta;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{

    public function __construct()
    {
        $this->middleware('role:' . config('app.admin_role') . '-' . config('app.anfitriona_role') . '-' . config('app.cocina_role') . '-' . config('app.garzon_role') . '-' . config('app.masoterapeuta_role') . '-' . config('app.barman_role'));
    }

    public function show()
    {

        $reservas = Reserva::all();
        $ventas = Venta::all();



        // Contar el número total de clientes
        $totalClientes = Cliente::count();

        // Contar el número total de reservas
        $totalReservas = Reserva::count();

        $totalConsumos = Consumo::count();

        $insumosCriticos = Insumo::whereColumn('cantidad', '<=', 'stock_critico')->get();

        $masajesAsignados = Masaje::count();

        $user = auth()->user();

        $inicioSemana = Carbon::now()->startOfWeek(); // Por defecto, inicia el lunes
        $finSemana = Carbon::now()->endOfWeek(); // Termina el domingo

        // Consulta para contar las asignaciones que caen dentro de la semana actual
        $asignacionesSemanaActual = Asignacion::whereBetween('fecha', [$inicioSemana, $finSemana])->count();



        if ($user->has_role(config('app.admin_role'))) {

            return view('themes.backoffice.pages.admin.show', compact('totalClientes', 'totalReservas', 'insumosCriticos', 'masajesAsignados', 'asignacionesSemanaActual', 'totalConsumos'));
        }

        if ($user->has_role(config('app.anfitriona_role'))) {
            return redirect()->action([ReservaController::class, 'index']);
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
        $masajes = Masaje::with('users', 'visitas');

        // Obtener usuarios con el rol de masoterapeuta
        $masoterapeutas = User::whereHas('roles', function ($query) {
            $query->where('name', 'masoterapeuta');
        })->get();

        // Configurar la semana para que comience el lunes
        Carbon::setWeekStartsAt(Carbon::MONDAY);
        Carbon::setWeekEndsAt(Carbon::SUNDAY);

        // Obtener la cantidad de masajes realizados por cada masoterapeuta en la semana en curso
        $inicioSemana = Carbon::now()->startOfWeek();
        $finSemana = Carbon::now()->endOfWeek();

        // Definir los días de la semana en español
        $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        // Calcular las fechas de cada día de la semana
        $fechasDiasSemana = [];
        foreach ($diasSemana as $indice => $dia) {
            $fechasDiasSemana[$dia] = $inicioSemana->copy()->addDays($indice)->toDateString();
        }

        // Crear un array para almacenar la cantidad de masajes por semana y por día por masoterapeuta
        $cantidadMasajesPorSemana = [];
        $cantidadMasajesPorDia = [];

        foreach ($masoterapeutas as $masoterapeuta) {
            // Cantidad total de masajes en la semana por masoterapeuta
            $cantidadMasajesPorSemana[$masoterapeuta->id] = Masaje::where('user_id', $masoterapeuta->id)
                ->whereBetween('created_at', [$inicioSemana, $finSemana])
                ->count();

            // Cantidad de masajes por cada día de la semana
            $masajesPorDia = Masaje::where('user_id', $masoterapeuta->id)
                ->whereBetween('created_at', [$inicioSemana, $finSemana])
                ->get()
                ->groupBy(function ($masaje) {
                    return Carbon::parse($masaje->created_at)->format('N');
                });

            // Crear un array para contener la cantidad de masajes por día
            $cantidadMasajesPorDia[$masoterapeuta->id] = [];
            foreach ($diasSemana as $indice => $dia) {
                $diaNumero = $indice + 1;
                $cantidadMasajesPorDia[$masoterapeuta->id][$dia] = isset($masajesPorDia[$diaNumero]) ? $masajesPorDia[$diaNumero]->count() : 0;
            }
        }

        return view('themes.backoffice.pages.admin.index', [
            'masoterapeutas' => $masoterapeutas,
            'cantidadMasajesPorSemana' => $cantidadMasajesPorSemana,
            'cantidadMasajesPorDia' => $cantidadMasajesPorDia,
            'diasSemana' => $diasSemana,

            'fechasDiasSemana' => $fechasDiasSemana,
        ]);
    }

    public function team()
    {
        Carbon::setLocale('es');
        // Obtener usuarios con roles especificos
        $usuarios = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['anfitriona', 'barman', 'cocina', 'garzon']);
        })->get();

        $inicioSemana = Carbon::now()->startOfWeek();
        $finSemana = Carbon::now()->endOfWeek();

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
        $propinasPorDia = [];
        $totalPropinasSemana = 0;
        $diaTrabajado = null;

        foreach ($detallesPorFecha as $detalle) {
            $fecha = Carbon::parse($detalle->fecha)->locale('es')->isoFormat('dddd');
            $fecha = ucfirst($fecha); // Convertir la primera letra a mayúscula para coincidir
            $diaTrabajado = Carbon::parse($detalle->fecha)->format('Y-m-d');
            if (isset($asignacionesPorDia[$fecha])) {
                $totalUsuarios = $asignacionesPorDia[$fecha]->pluck('users')->flatten()->count();
                $propinaPorUsuario = $totalUsuarios > 0 ? ($detalle->total_subtotal / $totalUsuarios) : 0;

                // $propinasPorDia[$fecha] = $propinaPorUsuario;
                $propinasPorDia[$fecha] = ['propina' => $propinaPorUsuario, 'dia_trabajado' => $diaTrabajado];

                $totalPropinasSemana += $detalle->total_subtotal;

            } else {
                // $propinasPorDia[$fecha] = 0;
                $propinasPorDia[$fecha] = ['propina' => 0,
                    'dia_trabajado' => $diaTrabajado];
            }
        }

        // Calcular el total a pagar por usuario en la semana
        $pagoBasePorUsuario = Cache::get('sueldoBase') ?? 45000;
        $totalPorUsuario = [];

        foreach ($usuarios as $usuario) {
            $totalDiasAsignados = 0;
            $propinaUsuario = 0;

            foreach ($diasSemana as $dia) {
                if (isset($asignacionesPorDia[$dia])) {
                    $usuariosDia = $asignacionesPorDia[$dia]->pluck('users')->flatten();

                    // Verifica si el usuario está asignado en este día
                    if ($usuariosDia->contains('id', $usuario->id)) {
                        $totalDiasAsignados++;
                        $propinaUsuario += $propinasPorDia[$dia]['propina'] ?? 0;

                    }
                }
            }

            // Calcular el total a pagar para el usuario
            $totalPorUsuario[$usuario->name] = ($totalDiasAsignados * $pagoBasePorUsuario) + $propinaUsuario;
        }

        $totalSueldoGeneral = array_sum($totalPorUsuario);

        // Calcular las fechas exactas de los días de la semana
        $fechasSemana = [];
        for ($i = 0; $i < 7; $i++) {
            $fechasSemana[$diasSemana[$i]] = $inicioSemana->copy()->addDays($i)->format('Y-m-d');
        }

        return view('themes.backoffice.pages.admin.team', [
            'diasSemana' => $diasSemana,
            'asignacionesPorDia' => $asignacionesPorDia,
            'propinasPorDia' => $propinasPorDia,
            'totalPorUsuario' => $totalPorUsuario,
            'totalSueldos' => $totalSueldoGeneral,
            'usuarios' => $usuarios,
            'diaT' => $diaTrabajado,
            'base' => $pagoBasePorUsuario,
            'fechasSemana' => $fechasSemana,
        ]);
    }


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
            ->paginate(12); // 12 meses por página

        return view('themes.backoffice.pages.admin.finanzas.ingresos', [
            'estadoMensual' => $estadoMensual
        ]);
    }

    
    public function detalleMes($anio, $mes)
    {
        $query = Venta::select(
                DB::raw('DATE(reservas.fecha_visita) as fecha'),
                DB::raw('DAY(reservas.fecha_visita) AS dia'),
                DB::raw('SUM(ventas.abono_programa) as abono'),
                DB::raw('SUM(ventas.total_pagar) as pendiente'),
                DB::raw('SUM(programas.valor_programa * reservas.cantidad_personas) as total_pagar')
            )
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->join('programas', 'reservas.id_programa', '=', 'programas.id')
            ->whereMonth('reservas.fecha_visita', $mes)
            ->whereYear('reservas.fecha_visita', $anio)
            ->groupBy(DB::raw('DATE(reservas.fecha_visita)'), DB::raw('DAY(reservas.fecha_visita)'))
            ->orderBy('fecha', 'desc');
    
        $result = $query->get();
    
        // Paginación manual
        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $result->slice(($currentPage - 1) * $perPage)->values();
        $ventasAgrupadas = new LengthAwarePaginator($currentItems, $result->count(), $perPage, $currentPage, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    
        $ingresosVentas = $result->sum('abono');
        $ventasPendientes = $result->sum('pendiente');
    
        // Mantener resumen por tipo de transacción
        $tiposTransacciones = TipoTransaccion::all()->map(function ($tipo) use ($anio, $mes) {
            $abono = Venta::where('id_tipo_transaccion_abono', $tipo->id)
                          ->whereHas('reserva', function ($query) use ($mes, $anio) {
                              $query->whereMonth('fecha_visita', $mes)
                                    ->whereYear('fecha_visita', $anio);
                          })
                          ->count();
    
            $diferencia = Venta::where('id_tipo_transaccion_diferencia', $tipo->id)
                               ->whereHas('reserva', function ($query) use ($mes, $anio) {
                                   $query->whereMonth('fecha_visita', $mes)
                                         ->whereYear('fecha_visita', $anio);
                               })
                               ->count();
    
            $tipo->total_abonos = $abono;
            $tipo->total_diferencias = $diferencia;
    
            return $tipo;
        });
    
        $nombreMes = Carbon::createFromDate($anio, $mes, 1)
            ->locale('es')->translatedFormat('F Y');
    
        return view('themes.backoffice.pages.admin.finanzas.detalle-mes', compact(
            'ventasAgrupadas', 'nombreMes', 'anio', 'mes',
            'ingresosVentas', 'ventasPendientes', 'tiposTransacciones'
        ));
    }
    

    // public function OLDdetalleMes($anio, $mes)
    // {
    //     $ingresosVentas = 0;
    //     $ventasPendientes = 0;

    //     $ventas = Venta::whereHas('reserva', function($query) use ($mes, $anio){
    //         $query->whereMonth('fecha_visita', $mes)
    //         ->whereYear('fecha_visita', $anio);
    //     })->paginate(20);

    //     $tiposTransacciones = TipoTransaccion::all()->map(function ($tipo) use ($anio, $mes) {
    //         $abono = Venta::where('id_tipo_transaccion_abono', $tipo->id)
    //                       ->whereHas('reserva', function ($query) use ($mes, $anio) {
    //                           $query->whereMonth('fecha_visita', $mes)
    //                                 ->whereYear('fecha_visita', $anio);
    //                       })
    //                       ->count();
    
    //         $diferencia = Venta::where('id_tipo_transaccion_diferencia', $tipo->id)
    //                            ->whereHas('reserva', function ($query) use ($mes, $anio) {
    //                                $query->whereMonth('fecha_visita', $mes)
    //                                      ->whereYear('fecha_visita', $anio);
    //                            })
    //                            ->count();
    
    //         $tipo->total_abonos = $abono;
    //         $tipo->total_diferencias = $diferencia;
    
    //         return $tipo;
    //     });
    

        
    //     foreach ($ventas as $venta) {
    //         $ingresosVentas += $venta->abono_programa;
    //         $ingresosVentas += $venta->diferencia_programa;
    //         $ventasPendientes += $venta->total_pagar;
    //     }

    //     // $ventas = Venta::whereYear('created_at', $anio)
    //     //     ->whereMonth('created_at', $mes)
    //     //     ->paginate(20); // o sin paginar, si prefieres

    //     $nombreMes = Carbon::createFromDate($anio, $mes, 1)
    //         ->locale('es')->translatedFormat('F Y');

    //     return view('themes.backoffice.pages.admin.finanzas.detalle-mes', compact('ventas', 'nombreMes', 'anio', 'mes', 'ingresosVentas', 'ventasPendientes', 'tiposTransacciones'));


    // }

    public function ingresosDiarios($anio, $mes, $dia)
    {
        $ingresosVentas = 0;
        $ventasPendientes = 0;
        // dd($dia);

        $ventas = Venta::whereHas('reserva', function($query) use ($mes, $anio, $dia){
            $query->whereMonth('fecha_visita', $mes)
            ->whereYear('fecha_visita', $anio)
            ->whereDay('fecha_visita', $dia);
        })->paginate(20);

        $tiposTransacciones = TipoTransaccion::all()->map(function ($tipo) use ($anio, $mes, $dia) {
            $abono = Venta::where('id_tipo_transaccion_abono', $tipo->id)
                          ->whereHas('reserva', function ($query) use ($mes, $anio, $dia) {
                              $query->whereMonth('fecha_visita', $mes)
                                    ->whereYear('fecha_visita', $anio)
                                    ->whereDay('fecha_visita', $dia);
                          })
                          ->count();
    
            $diferencia = Venta::where('id_tipo_transaccion_diferencia', $tipo->id)
                               ->whereHas('reserva', function ($query) use ($mes, $anio, $dia) {
                                   $query->whereMonth('fecha_visita', $mes)
                                         ->whereYear('fecha_visita', $anio)
                                         ->whereDay('fecha_visita', $dia);
                               })
                               ->count();
    
            $tipo->total_abonos = $abono;
            $tipo->total_diferencias = $diferencia;
    
            return $tipo;
        });
    

        
        foreach ($ventas as $venta) {
            $ingresosVentas += $venta->abono_programa;
            $ingresosVentas += $venta->diferencia_programa;
            $ventasPendientes += $venta->total_pagar;
        }

        // $ventas = Venta::whereYear('created_at', $anio)
        //     ->whereMonth('created_at', $mes)
        //     ->paginate(20); // o sin paginar, si prefieres

        $nombreMes = Carbon::createFromDate($anio, $mes, $dia)
        ->locale('es')
        ->translatedFormat('d \d\e F \d\e Y');

        return view('themes.backoffice.pages.admin.finanzas.detalle-dia', compact('ventas', 'nombreMes', 'anio', 'mes','dia', 'ingresosVentas', 'ventasPendientes', 'tiposTransacciones'));


    }

    public function consumos()
    {
        $consumoMensual = DB::table('consumos')
        ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
        ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
        ->selectRaw('
            YEAR(reservas.fecha_visita) AS anio,
            MONTH(reservas.fecha_visita) AS mes,
            COUNT(*) AS total_consumos,
            SUM(consumos.subtotal) AS subtotales
        ')
        ->groupBy(DB::raw('YEAR(reservas.fecha_visita)'), DB::raw('MONTH(reservas.fecha_visita)'))
        ->orderByDesc(DB::raw('YEAR(reservas.fecha_visita)'))
        ->orderByDesc(DB::raw('MONTH(reservas.fecha_visita)'))
        ->paginate(12); // 12 meses por página


        return view('themes.backoffice.pages.admin.consumos.mensuales', compact('consumoMensual'));
    }

    public function consumosMensuales($anio,$mes) 
    {

        $ventas = Venta::with(['consumo.detallesConsumos.producto'])->whereHas('reserva', function($query) use ($mes, $anio) {
            $query->whereMonth('fecha_visita', $mes)
                  ->whereYear('fecha_visita', $anio);
        })->get();
        
        

        return view('themes.backoffice.pages.admin.consumos.detalle', compact('ventas'));
    }

}
