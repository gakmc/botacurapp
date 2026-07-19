<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class IotController extends Controller
{
    private function validarToken(Request $request)
    {
        $tokenEnv = env('IOT_API_TOKEN');
        $tokenReq = $request->header('X-BOTACURA-IOT-TOKEN', $request->query('token'));

        return $tokenEnv && $tokenReq && hash_equals($tokenEnv, $tokenReq);
    }

    private function primeraColumnaExistente($tabla, $candidatas)
    {
        foreach ($candidatas as $columna) {
            if (Schema::hasColumn($tabla, $columna)) {
                return $columna;
            }
        }

        return null;
    }

    private function columnasTabla($tabla)
    {
        try {
            return array_map(function ($c) {
                return $c->Field;
            }, DB::select("SHOW COLUMNS FROM {$tabla}"));
        } catch (\Exception $e) {
            return [];
        }
    }

    public function ping(Request $request)
    {
        if (!$this->validarToken($request)) {
            return response()->json([
                'ok' => false,
                'error' => 'Token inválido'
            ], 401);
        }

        return response()->json([
            'ok' => true,
            'estado' => 'online',
            'mensaje' => 'API IOT Botacura funcionando',
            'fecha_hora_servidor' => Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }

    public function proximaTinaja(Request $request)
    {
        if (!$this->validarToken($request)) {
            return response()->json([
                'ok' => false,
                'error' => 'Token inválido'
            ], 401);
        }

        /*
         |--------------------------------------------------------------------------
         | Fuente principal: visitas
         |--------------------------------------------------------------------------
         | La próxima hora de tinaja se toma desde visitas.
         | visitas.id_reserva enlaza con reservas.
         | reservas.id_cliente enlaza con clientes, si existe.
         |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('visitas')) {
            return response()->json([
                'ok' => false,
                'estado' => 'schema_error',
                'mensaje' => 'No existe la tabla visitas'
            ], 500);
        }

        if (!Schema::hasColumn('visitas', 'id_reserva')) {
            return response()->json([
                'ok' => false,
                'estado' => 'schema_error',
                'mensaje' => 'La tabla visitas no tiene columna id_reserva',
                'columnas_visitas' => $this->columnasTabla('visitas')
            ], 500);
        }

        /*
         |--------------------------------------------------------------------------
         | Detectar columnas reales en visitas
         |--------------------------------------------------------------------------
        */

        $campoFecha = $this->primeraColumnaExistente('visitas', [
            'fecha',
            'fecha_visita',
            'fecha_reserva',
            'fecha_ingreso',
            'dia',
            'created_at'
        ]);

        $campoHora = $this->primeraColumnaExistente('visitas', [
            'hora_tinaja',
            'hora',
            'hora_visita',
            'hora_reserva',
            'hora_inicio',
            'inicio',
            'hora_ingreso'
        ]);

        if (!$campoFecha || !$campoHora) {
            return response()->json([
                'ok' => false,
                'estado' => 'schema_incompleto',
                'mensaje' => 'No se encontraron columnas de fecha y hora en visitas',
                'campo_fecha_detectado' => $campoFecha,
                'campo_hora_detectado' => $campoHora,
                'columnas_visitas' => $this->columnasTabla('visitas')
            ], 500);
        }

        /*
         |--------------------------------------------------------------------------
         | Detectar relación con clientes
         |--------------------------------------------------------------------------
        */

        $existeReservas = Schema::hasTable('reservas');
        $existeClientes = Schema::hasTable('clientes');

        $campoClienteReserva = null;

        if ($existeReservas) {
            $campoClienteReserva = $this->primeraColumnaExistente('reservas', [
                'id_cliente',
                'cliente_id',
                'id_clientes'
            ]);
        }

        $campoNombreCliente = null;
        $campoApellidoCliente = null;

        if ($existeClientes) {
            $campoNombreCliente = $this->primeraColumnaExistente('clientes', [
                'nombre',
                'nombres',
                'nombre_cliente',
                'razon_social'
            ]);

            $campoApellidoCliente = $this->primeraColumnaExistente('clientes', [
                'apellido',
                'apellidos',
                'apellido_cliente'
            ]);
        }

        $campoNombreReserva = null;

        if ($existeReservas) {
            $campoNombreReserva = $this->primeraColumnaExistente('reservas', [
                'nombre',
                'nombre_cliente',
                'cliente',
                'nombre_reserva'
            ]);
        }

        /*
         |--------------------------------------------------------------------------
         | Query base desde visitas
         |--------------------------------------------------------------------------
        */

        $hoy = Carbon::today()->format('Y-m-d');
        $horaActual = Carbon::now()->format('H:i:s');

        $query = DB::table('visitas as v');

        if ($existeReservas) {
            $query->leftJoin('reservas as r', 'r.id', '=', 'v.id_reserva');
        }

        if ($existeReservas && $existeClientes && $campoClienteReserva) {
            $query->leftJoin('clientes as c', 'c.id', '=', 'r.' . $campoClienteReserva);
        }

        /*
         |--------------------------------------------------------------------------
         | Selección dinámica segura
         |--------------------------------------------------------------------------
        */

        $query->select(
            'v.id as visita_id',
            'v.id_reserva',
            DB::raw("v.`{$campoFecha}` as fecha_visita"),
            DB::raw("v.`{$campoHora}` as hora_tinaja")
        );

        if ($existeClientes && $campoNombreCliente && $campoApellidoCliente) {
            $query->addSelect(DB::raw("CONCAT(c.`{$campoNombreCliente}`, ' ', c.`{$campoApellidoCliente}`) as nombre_cliente"));
        } elseif ($existeClientes && $campoNombreCliente) {
            $query->addSelect(DB::raw("c.`{$campoNombreCliente}` as nombre_cliente"));
        } elseif ($existeReservas && $campoNombreReserva) {
            $query->addSelect(DB::raw("r.`{$campoNombreReserva}` as nombre_cliente"));
        } else {
            $query->addSelect(DB::raw("CONCAT('Reserva ', v.id_reserva) as nombre_cliente"));
        }

        /*
         |--------------------------------------------------------------------------
         | Filtro próxima visita
         |--------------------------------------------------------------------------
         | - Fecha mayor a hoy
         | - O fecha de hoy con hora igual o superior a la hora actual
         |--------------------------------------------------------------------------
        */

        $query->whereNotNull("v.{$campoFecha}")
              ->whereNotNull("v.{$campoHora}")
              ->where(function ($q) use ($campoFecha, $campoHora, $hoy, $horaActual) {
                  $q->whereDate("v.{$campoFecha}", '>', $hoy)
                    ->orWhere(function ($qq) use ($campoFecha, $campoHora, $hoy, $horaActual) {
                        $qq->whereDate("v.{$campoFecha}", '=', $hoy)
                           ->where("v.{$campoHora}", '>=', $horaActual);
                    });
              })
              ->orderBy("v.{$campoFecha}", 'asc')
              ->orderBy("v.{$campoHora}", 'asc');

        $visita = $query->first();

        if (!$visita) {
            return response()->json([
                'ok' => true,
                'estado' => 'sin_reserva',
                'mensaje' => 'No hay próxima visita con tinaja',
                'visita_id' => null,
                'reserva_id' => null,
                'nombre' => null,
                'fecha_reserva' => null,
                'hora_tinaja' => null,
                'fecha_hora_tinaja' => null,
                'tinaja_numero' => null,
                'temperatura_objetivo' => 40,
                'margen_seguridad_min' => 20,
                'fuente' => 'visitas',
                'fecha_hora_servidor' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
        }

        /*
         |--------------------------------------------------------------------------
         | Normalizar fecha y hora
         |--------------------------------------------------------------------------
        */

        $fecha = Carbon::parse($visita->fecha_visita)->format('Y-m-d');

        $horaRaw = $visita->hora_tinaja;

        if (strlen($horaRaw) >= 5) {
            $hora = substr($horaRaw, 0, 5);
        } else {
            $hora = Carbon::parse($horaRaw)->format('H:i');
        }

        $fechaHoraTinaja = Carbon::parse($fecha . ' ' . $hora . ':00');

        /*
         |--------------------------------------------------------------------------
         | Regla Botacura:
         | Hora en punto  → Tinaja 2
         | Hora en media  → Tinaja 1
         |--------------------------------------------------------------------------
        */

        $minuto = intval($fechaHoraTinaja->format('i'));

        if ($minuto === 0) {
            $tinajaNumero = 2;
        } elseif ($minuto === 30) {
            $tinajaNumero = 1;
        } else {
            $tinajaNumero = null;
        }

        return response()->json([
            'ok' => true,
            'estado' => 'con_reserva',
            'mensaje' => 'Próxima visita con tinaja encontrada',
            'visita_id' => $visita->visita_id,
            'reserva_id' => $visita->id_reserva,
            'nombre' => $visita->nombre_cliente ?? 'Reserva tinaja',
            'fecha_reserva' => $fecha,
            'hora_tinaja' => $hora,
            'fecha_hora_tinaja' => $fechaHoraTinaja->format('Y-m-d H:i:s'),
            'tinaja_numero' => $tinajaNumero,
            'temperatura_objetivo' => 40,
            'margen_seguridad_min' => 20,
            'fuente' => 'visitas',
            'campo_fecha_usado' => $campoFecha,
            'campo_hora_usado' => $campoHora,
            'fecha_hora_servidor' => Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }
}
