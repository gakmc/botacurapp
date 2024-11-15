<?php

namespace App\Http\Controllers;

use App\Asignacion;
use App\Cliente;
use App\Insumo;
use App\Masaje;
use App\Reserva;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{

    public function __construct()
    {
        $this->middleware('role:' . config('app.admin_role') . '-' . config('app.anfitriona_role') . '-' . config('app.cocina_role') . '-' . config('app.garzon_role') . '-' . config('app.masoterapeuta_role'));
    }

    public function show()
    {
        // Contar el número total de clientes
        $totalClientes = Cliente::count();

        // Contar el número total de reservas
        $totalReservas = Reserva::count();

        $insumosCriticos = Insumo::whereColumn('cantidad', '<=', 'stock_critico')->get();

        $masajesAsignados = Masaje::count();

        $user = auth()->user();

        $inicioSemana = Carbon::now()->startOfWeek(); // Por defecto, inicia el lunes
        $finSemana = Carbon::now()->endOfWeek(); // Termina el domingo

        // Consulta para contar las asignaciones que caen dentro de la semana actual
        $asignacionesSemanaActual = Asignacion::whereBetween('fecha', [$inicioSemana, $finSemana])->count();

        if ($user->has_role(config('app.admin_role'))) {

            return view('themes.backoffice.pages.admin.show', compact('totalClientes', 'totalReservas', 'insumosCriticos', 'masajesAsignados', 'asignacionesSemanaActual'));
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
        ]);
    }

    public function team()
    {
        Carbon::setLocale('es');
        // Obtener usuarios con el rol de masoterapeuta
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
        $detallesPorFecha = DB::table('detalles_consumos')
            ->selectRaw('reservas.fecha_visita AS fecha, SUM(IF(detalles_consumos.genera_propina = true, detalles_consumos.subtotal * 0.1, 0)) AS total_subtotal')
            ->join('consumos', 'detalles_consumos.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->whereBetween('reservas.fecha_visita', [$inicioSemana, $finSemana])
            ->groupBy('reservas.fecha_visita')
            ->get();

        // Crear un array para almacenar las propinas por día y dividirlas entre los usuarios asignados
        $propinasPorDia = [];
        $totalPropinasSemana = 0;

        foreach ($detallesPorFecha as $detalle) {
            $fecha = Carbon::parse($detalle->fecha)->locale('es')->isoFormat('dddd');
            $fecha = ucfirst($fecha); // Convertir la primera letra a mayúscula para coincidir
            if (isset($asignacionesPorDia[$fecha])) {
                $totalUsuarios = $asignacionesPorDia[$fecha]->pluck('users')->flatten()->count();

                // $propinasPorDia[$fecha] = $totalUsuarios > 0 ? ($detalle->total_subtotal / $totalUsuarios) : 0;

                $propinaPorUsuario = $totalUsuarios > 0 ? ($detalle->total_subtotal / $totalUsuarios) : 0;
                $propinasPorDia[$fecha] = $propinaPorUsuario;
                $totalPropinasSemana += $detalle->total_subtotal;

            } else {
                $propinasPorDia[$fecha] = 0;
            }
        }

        // Calcular el total a pagar por usuario en la semana
        $pagoBasePorUsuario = 40000; // Asume que todos los roles tienen un pago base de $40,000 por día trabajado
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
                        $propinaUsuario += $propinasPorDia[$dia] ?? 0;
                    }
                }
            }

            // Calcular el total a pagar para el usuario
            $totalPorUsuario[$usuario->name] = ($totalDiasAsignados * $pagoBasePorUsuario) + $propinaUsuario;
        }

        $totalSueldoGeneral = array_sum($totalPorUsuario);

        return view('themes.backoffice.pages.admin.team', [
            'diasSemana' => $diasSemana,
            'asignacionesPorDia' => $asignacionesPorDia,
            'propinasPorDia' => $propinasPorDia,
            'totalPorUsuario' => $totalPorUsuario,
            'totalSueldos' => $totalSueldoGeneral
        ]);
    }

}
