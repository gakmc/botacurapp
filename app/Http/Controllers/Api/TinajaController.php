<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * TinajaController
 * GET /api/iot/tinajas/proxima-reserva
 */
class TinajaController extends Controller
{
    /**
     * Mismo mecanismo que IotController::validarToken() (header
     * X-BOTACURA-IOT-TOKEN contra env('IOT_API_TOKEN')), duplicado aca para
     * no depender de un metodo privado de otro controller. Se exige solo en
     * el endpoint que escribe estado (setInversion) -- los GET de reserva y
     * agenda no lo validaban antes de este cambio y se dejan igual para no
     * romper el polling que Home Assistant ya tiene configurado.
     */
    private function validarToken(Request $request)
    {
        $tokenEnv = env('IOT_API_TOKEN');
        $tokenReq = $request->header('X-BOTACURA-IOT-TOKEN', $request->query('token'));
        return $tokenEnv && $tokenReq && hash_equals($tokenEnv, $tokenReq);
    }

    /**
     * true si el toggle "Invertir Tinajas" esta activo: en ese caso los
     * horarios que normalmente son de Tinaja 1 (minuto :45) se muestran como
     * Tinaja 2, y viceversa (minuto :15 -> Tinaja 1). No se reasigna ninguna
     * reserva en la base de datos, solo se invierte la etiqueta con la que
     * se arma la respuesta -- pensado para cuando una tinaja tiene un
     * problema (ej. sin gas) y hay que pasar su horario a la otra.
     */
    private function estaInvertido(): bool
    {
        $row = DB::table('tinajas_config')->where('id', 1)->first();

        return $row ? (bool) $row->invertido : false;
    }

    /**
     * GET /api/iot/tinajas/estado-inversion
     */
    public function estadoInversion(Request $request)
    {
        return response()->json([
            'ok'        => true,
            'invertido' => $this->estaInvertido(),
        ]);
    }

    /**
     * POST /api/iot/tinajas/set-inversion
     * body: { "invertido": true|false }
     */
    public function setInversion(Request $request)
    {
        if (!$this->validarToken($request)) {
            return response()->json([
                'ok'    => false,
                'error' => 'Token inválido',
            ], 401);
        }

        $invertido = filter_var($request->input('invertido'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($invertido === null) {
            return response()->json([
                'ok'    => false,
                'error' => 'Falta el campo invertido (true/false)',
            ], 422);
        }

        DB::table('tinajas_config')->updateOrInsert(
            ['id' => 1],
            ['invertido' => $invertido, 'updated_at' => now()]
        );

        return response()->json([
            'ok'        => true,
            'invertido' => $invertido,
        ]);
    }

    public function proximaReserva()
    {
        $inv = $this->estaInvertido();

        return response()->json([
            'ok'                => true,
            'tinaja_1'          => $this->getProximaReserva($inv ? '15' : '45'),
            'tinaja_2'          => $this->getProximaReserva($inv ? '45' : '15'),
            'sauna'             => $this->getProximaSauna(),
            'masaje_container'  => $this->getProximaMasaje('container'),
            'masaje_palmeras'   => $this->getProximaMasaje('palmeras'),
            'invertido'         => $inv,
            'consultado_en'     => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * GET /api/iot/tinajas/agenda-dia
     * Lista completa de reservas de HOY que aun no han terminado, por tinaja,
     * para que Home Assistant pueda mantener 40C durante toda la jornada
     * (incluyendo horarios no continuos) y apagar recien tras la ultima.
     */
    public function agendaDia()
    {
        $inv = $this->estaInvertido();

        return response()->json([
            'ok'             => true,
            'fecha'          => now()->format('Y-m-d'),
            'tinaja_1'       => $this->getAgendaTinaja($inv ? '15' : '45'),
            'tinaja_2'       => $this->getAgendaTinaja($inv ? '45' : '15'),
            'sauna'          => $this->getAgendaSauna(),
            'invertido'      => $inv,
            'consultado_en'  => now()->format('Y-m-d H:i:s'),
        ]);
    }

    private function getAgendaTinaja(string $minutos): array
    {
        $hoy   = now()->format('Y-m-d');
        $ahora = now()->format('Y-m-d H:i:s');

        $rows = DB::table('visitas as v')
            ->join('reservas as r', 'v.id_reserva', '=', 'r.id')
            ->leftJoin('clientes as c', 'r.cliente_id', '=', 'c.id')
            ->select(
                'v.horario_tinaja',
                DB::raw("CONCAT(r.fecha_visita, ' ', v.horario_tinaja) AS datetime_reserva"),
                'c.nombre_cliente AS cliente'
            )
            ->whereNotNull('v.horario_tinaja')
            ->where('r.fecha_visita', $hoy)
            ->whereRaw("TIME_FORMAT(v.horario_tinaja, '%i') = ?", [$minutos])
            ->whereRaw("DATE_ADD(CONCAT(r.fecha_visita, ' ', v.horario_tinaja), INTERVAL 45 MINUTE) > ?", [$ahora])
            ->orderBy('v.horario_tinaja', 'ASC')
            ->get();

        return $rows->map(function ($row) {
            return [
                'horario'          => substr($row->horario_tinaja, 0, 5),
                'datetime_reserva' => $row->datetime_reserva,
                'datetime_fin'     => date('Y-m-d H:i:s', strtotime($row->datetime_reserva . ' +45 minutes')),
                'cliente'          => $row->cliente ?? 'Sin nombre',
            ];
        })->values()->all();
    }

    private function getAgendaSauna(): array
    {
        $hoy   = now()->format('Y-m-d');
        $ahora = now()->format('Y-m-d H:i:s');

        $rows = DB::table('visitas as v')
            ->join('reservas as r', 'v.id_reserva', '=', 'r.id')
            ->leftJoin('clientes as c', 'r.cliente_id', '=', 'c.id')
            ->select(
                'v.horario_sauna',
                DB::raw("CONCAT(r.fecha_visita, ' ', v.horario_sauna) AS datetime_reserva"),
                'c.nombre_cliente AS cliente'
            )
            ->whereNotNull('v.horario_sauna')
            ->where('r.fecha_visita', $hoy)
            ->whereRaw("DATE_ADD(CONCAT(r.fecha_visita, ' ', v.horario_sauna), INTERVAL 15 MINUTE) > ?", [$ahora])
            ->orderBy('v.horario_sauna', 'ASC')
            ->get();

        return $rows->map(function ($row) {
            return [
                'horario'          => substr($row->horario_sauna, 0, 5),
                'datetime_reserva' => $row->datetime_reserva,
                'datetime_fin'     => date('Y-m-d H:i:s', strtotime($row->datetime_reserva . ' +15 minutes')),
                'cliente'          => $row->cliente ?? 'Sin nombre',
            ];
        })->values()->all();
    }

    private function getProximaSauna(): ?array
    {
        $ahora = now()->format('Y-m-d H:i:s');

        $row = DB::table('visitas as v')
            ->join('reservas as r', 'v.id_reserva', '=', 'r.id')
            ->leftJoin('clientes as c', 'r.cliente_id', '=', 'c.id')
            ->select(
                'r.fecha_visita',
                'v.horario_sauna',
                DB::raw("CONCAT(r.fecha_visita, ' ', v.horario_sauna) AS datetime_reserva"),
                'c.nombre_cliente AS cliente'
            )
            ->whereNotNull('v.horario_sauna')
            ->whereRaw("DATE_ADD(CONCAT(r.fecha_visita, ' ', v.horario_sauna), INTERVAL 15 MINUTE) > ?", [$ahora])
            ->orderBy('r.fecha_visita', 'ASC')
            ->orderBy('v.horario_sauna', 'ASC')
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'fecha_visita'     => $row->fecha_visita,
            'horario'          => substr($row->horario_sauna, 0, 5),
            'datetime_reserva' => $row->datetime_reserva,
            'cliente'          => $row->cliente ?? 'Sin nombre',
        ];
    }

    private function getProximaMasaje(string $lugarKeyword): ?array
    {
        $ahora = now()->format('Y-m-d H:i:s');

        $row = DB::table('masajes as m')
            ->join('reservas as r', 'm.id_reserva', '=', 'r.id')
            ->join('lugares_masajes as lm', 'm.id_lugar_masaje', '=', 'lm.id')
            ->leftJoin('clientes as c', 'r.cliente_id', '=', 'c.id')
            ->select(
                'r.fecha_visita',
                'm.horario_masaje',
                'lm.nombre AS lugar',
                DB::raw("CONCAT(r.fecha_visita, ' ', m.horario_masaje) AS datetime_reserva"),
                'c.nombre_cliente AS cliente'
            )
            ->whereNotNull('m.horario_masaje')
            ->where('lm.nombre', 'LIKE', "%{$lugarKeyword}%")
            ->whereRaw("DATE_ADD(CONCAT(r.fecha_visita, ' ', m.horario_masaje), INTERVAL (30 + (m.tiempo_extra * 30)) MINUTE) > ?", [$ahora])
            ->orderBy('r.fecha_visita', 'ASC')
            ->orderBy('m.horario_masaje', 'ASC')
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'fecha_visita'     => $row->fecha_visita,
            'horario'          => substr($row->horario_masaje, 0, 5),
            'datetime_reserva' => $row->datetime_reserva,
            'cliente'          => $row->cliente ?? 'Sin nombre',
            'lugar'            => $row->lugar,
        ];
    }

    private function getProximaReserva(string $minutos): ?array
    {
        $ahora = now()->format('Y-m-d H:i:s');

        $row = DB::table('visitas as v')
            ->join('reservas as r', 'v.id_reserva', '=', 'r.id')
            ->leftJoin('clientes as c', 'r.cliente_id', '=', 'c.id')
            ->select(
                'r.fecha_visita',
                'v.horario_tinaja',
                DB::raw("CONCAT(r.fecha_visita, ' ', v.horario_tinaja) AS datetime_reserva"),
                'c.nombre_cliente AS cliente'
            )
            ->whereNotNull('v.horario_tinaja')
            ->whereRaw("TIME_FORMAT(v.horario_tinaja, '%i') = ?", [$minutos])
            ->whereRaw("DATE_ADD(CONCAT(r.fecha_visita, ' ', v.horario_tinaja), INTERVAL 45 MINUTE) > ?", [$ahora])
            ->orderBy('r.fecha_visita', 'ASC')
            ->orderBy('v.horario_tinaja', 'ASC')
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'fecha_visita'     => $row->fecha_visita,
            'horario'          => substr($row->horario_tinaja, 0, 5),
            'datetime_reserva' => $row->datetime_reserva,
            'cliente'          => $row->cliente ?? 'Sin nombre',
        ];
    }
}
