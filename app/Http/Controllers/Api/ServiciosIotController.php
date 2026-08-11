<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

/**
 * ServiciosIotController
 *
 * Devuelve la próxima reserva de cada servicio para Home Assistant.
 * GET /api/iot/servicios/proximas-reservas
 *
 * Respuesta:
 * {
 *   "ok": true,
 *   "sauna":            { "cliente": "...", "horario": "HH:MM", "fecha_visita": "..." } | null,
 *   "masaje_container": { "cliente": "...", "horario": "HH:MM", "fecha_visita": "..." } | null,
 *   "masaje_palmeras":  { "cliente": "...", "horario": "HH:MM", "fecha_visita": "..." } | null,
 *   "consultado_en": "YYYY-MM-DD HH:MM:SS"
 * }
 */
class ServiciosIotController extends Controller
{
    public function proximasReservas()
    {
        return response()->json([
            'ok'               => true,
            'sauna'            => $this->proximaSauna(),
            'masaje_container' => $this->proximaMasaje('container'),
            'masaje_palmeras'  => $this->proximaMasaje('palmeras'),
            'consultado_en'    => now()->format('Y-m-d H:i:s'),
        ]);
    }

    // ── Sauna ──────────────────────────────────────────────────────────────
    // Consulta visitas.horario_sauna: próxima visita con sauna agendado

    private function proximaSauna(): ?array
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

    // ── Masaje por lugar ───────────────────────────────────────────────────
    // Consulta masajes.horario_masaje JOIN lugares_masajes WHERE nombre LIKE %keyword%

    private function proximaMasaje(string $lugarKeyword): ?array
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
}
