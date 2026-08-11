<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

/**
 * TinajaController
 * GET /api/iot/tinajas/proxima-reserva
 */
class TinajaController extends Controller
{
    public function proximaReserva()
    {
        return response()->json([
            'ok'                => true,
            'tinaja_1'          => $this->getProximaReserva('45'),
            'tinaja_2'          => $this->getProximaReserva('15'),
            'sauna'             => $this->getProximaSauna(),
            'masaje_container'  => $this->getProximaMasaje('container'),
            'masaje_palmeras'   => $this->getProximaMasaje('palmeras'),
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
        return response()->json([
            'ok'             => true,
            'fecha'          => now()->format('Y-m-d'),
            'tinaja_1'       => $this->getAgendaTinaja('45'),
            'tinaja_2'       => $this->getAgendaTinaja('15'),
            'sauna'          => $this->getAgendaSauna(),
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

        if (!$row) return null;

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

        if (!$row) return null;

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

        if (!$row) return null;

        return [
            'fecha_visita'     => $row->fecha_visita,
            'horario'          => substr($row->horario_tinaja, 0, 5),
            'datetime_reserva' => $row->datetime_reserva,
            'cliente'          => $row->cliente ?? 'Sin nombre',
        ];
    }
}
