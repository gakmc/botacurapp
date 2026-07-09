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

    private function getProximaSauna(): ?array
    {
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
            ->whereRaw("CONCAT(r.fecha_visita, ' ', v.horario_sauna) > NOW()")
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
            ->whereRaw("CONCAT(r.fecha_visita, ' ', m.horario_masaje) > NOW()")
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
            ->whereRaw("CONCAT(r.fecha_visita, ' ', v.horario_tinaja) > NOW()")
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
