<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

/**
 * TinajaController
 *
 * Consulta la base de datos para obtener la próxima reserva de cada tinaja.
 * - Tinaja 1: horario_tinaja termina en :45 (ej: 11:45, 12:45, 15:45...)
 * - Tinaja 2: horario_tinaja termina en :15 (ej: 11:15, 12:15, 15:15...)
 *
 * Consumido por Home Assistant vía sensor REST para mostrar en el dashboard
 * y calcular el encendido automático de la calefacción.
 *
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
     * Próxima visita con sauna agendado (visitas.horario_sauna).
     */
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

    /**
     * Próximo masaje por lugar (masajes.horario_masaje JOIN lugares_masajes).
     */
    private function getProximaMasaje(string $lugarKeyword): ?array
    {