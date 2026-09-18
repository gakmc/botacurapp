<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DisponibilidadService
{
    private const ESPACIO_TIPOS_ESTACION = ['estacion_economico', 'estacion_intermedio', 'estacion_full'];
    private const ESPACIO_TIPOS_WELLNESS = ['wellness', 'terraza', 'reposera'];

    private const SUB_TIPOS_ESTACION = ['estacion_normal', 'estacion_grupal'];
    private const SUB_TIPOS_WELLNESS = ['terraza', 'reposera'];

    public function categoriaDeEspacioTipo(?string $espacioTipo): ?string
    {
        if (in_array($espacioTipo, self::ESPACIO_TIPOS_ESTACION, true)) {
            return 'estacion';
        }

        if (in_array($espacioTipo, self::ESPACIO_TIPOS_WELLNESS, true)) {
            return 'wellness';
        }

        return null;
    }

    private function personasPendientesPorCategoria(string $fecha): array
    {
        $ordenes = DB::table('woocommerce_orders')
            ->join('programas', 'woocommerce_orders.wc_product_id', '=', 'programas.wc_product_id')
            ->whereNull('woocommerce_orders.reserva_id')
            ->where('woocommerce_orders.procesado', 'pendiente')
            ->whereRaw('DATE(woocommerce_orders.fecha_visita_wc) = ?', [$fecha])
            ->whereNotNull('woocommerce_orders.cantidad_personas')
            ->select('programas.espacio_tipo', 'woocommerce_orders.cantidad_personas')
            ->get();

        return [
            'estacion' => (int) $ordenes->whereIn('espacio_tipo', self::ESPACIO_TIPOS_ESTACION)->sum('cantidad_personas'),
            'wellness' => (int) $ordenes->whereIn('espacio_tipo', self::ESPACIO_TIPOS_WELLNESS)->sum('cantidad_personas'),
        ];
    }

    private function libresTrasReservarPendientes($unidadesLibres, int $personasPendientes)
    {
        $restantes = $personasPendientes;
        $libres    = $unidadesLibres->sortByDesc('capacidad_max')->values();

        while ($restantes > 0 && $libres->isNotEmpty()) {
            $unidad     = $libres->shift();
            $restantes -= (int) $unidad->capacidad_max;
        }

        return $libres;
    }

    public function slotsTinaja(string $fecha, int $personasNuevas = 0): array
    {
        $max = (int) (config('app.cantidad_slot_spa') ?: 16);

        $usadosReservas = (int) DB::table('reservas')
            ->whereRaw('DATE(fecha_visita) = ?', [$fecha])
            ->selectRaw('COALESCE(SUM(CEIL(cantidad_personas / 5)), 0) as slots')
            ->value('slots');

        $usadosOrdenes = (int) DB::table('woocommerce_orders')
            ->whereNull('reserva_id')
            ->where('procesado', 'pendiente')
            ->whereRaw('DATE(fecha_visita_wc) = ?', [$fecha])
            ->whereNotNull('cantidad_personas')
            ->selectRaw('COALESCE(SUM(CEIL(cantidad_personas / 5)), 0) as slots')
            ->value('slots');

        $usados = $usadosReservas + $usadosOrdenes;
        $nuevos = $personasNuevas > 0 ? (int) ceil($personasNuevas / 5) : 0;

        return [
            'max_slots'    => $max,
            'usados'       => $usados,
            'slots_nuevos' => $nuevos,
            'disponibles'  => max(0, $max - $usados),
            'ok'           => ($usados + $nuevos) <= $max,
        ];
    }

    private function idsUbicacionesOcupadas(string $fecha)
    {
        $legado = DB::table('visitas')
            ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
            ->whereRaw('DATE(reservas.fecha_visita) = ?', [$fecha])
            ->whereNotNull('visitas.id_ubicacion')
            ->pluck('visitas.id_ubicacion');

        $nuevas = DB::table('reserva_ubicaciones')
            ->join('reservas', 'reserva_ubicaciones.id_reserva', '=', 'reservas.id')
            ->whereRaw('DATE(reservas.fecha_visita) = ?', [$fecha])
            ->pluck('reserva_ubicaciones.id_ubicacion');

        return $legado->merge($nuevas)->unique()->values();
    }

    public function unidadesLibresPorFecha(string $fecha): array
    {
        $ocupadas = $this->idsUbicacionesOcupadas($fecha);

        $ubicaciones = DB::table('ubicaciones')
            ->select('id', 'sub_tipo')
            ->where('activo', true)
            ->whereNotNull('sub_tipo')
            ->get();

        $contar = function (array $subTipos) use ($ubicaciones, $ocupadas) {
            $delGrupo = $ubicaciones->whereIn('sub_tipo', $subTipos);
            $libres   = $delGrupo->reject(function ($u) use ($ocupadas) {
                return $ocupadas->contains($u->id);
            });

            return ['total' => $delGrupo->count(), 'libres' => $libres->count()];
        };

        return [
            'estacion' => $contar(['estacion_normal', 'estacion_grupal']),
            'terraza'  => $contar(['terraza']),
            'reposera' => $contar(['reposera']),
        ];
    }

    public function resumen(string $fecha): array
    {
        $unidades = $this->unidadesLibresPorFecha($fecha);
        $labels   = ['estacion' => 'Estaciones', 'terraza' => 'Terrazas', 'reposera' => 'Reposeras'];

        $resumen = [];
        foreach (['estacion', 'terraza', 'reposera'] as $categoria) {
            $resumen[$categoria] = [
                'max'         => $unidades[$categoria]['total'],
                'usados'      => $unidades[$categoria]['total'] - $unidades[$categoria]['libres'],
                'disponibles' => $unidades[$categoria]['libres'],
                'label'       => $labels[$categoria],
            ];
        }

        return [
            'fecha'   => $fecha,
            'resumen' => $resumen,
            'tinaja'  => $this->slotsTinaja($fecha),
        ];
    }

    private function ubicacionesLibresConCapacidad(string $fecha)
    {
        $ocupadas = $this->idsUbicacionesOcupadas($fecha);

        return DB::table('ubicaciones')
            ->select('id', 'sub_tipo', 'capacidad_max')
            ->where('activo', true)
            ->whereNotNull('sub_tipo')
            ->get()
            ->reject(function ($u) use ($ocupadas) {
                return $ocupadas->contains($u->id);
            });
    }

    private function caben(int $personas, $unidadesLibres): bool
    {
        $restantes = $personas;

        foreach ($unidadesLibres->sortByDesc('capacidad_max') as $ubicacion) {
            if ($restantes <= 0) {
                break;
            }
            $restantes -= (int) $ubicacion->capacidad_max;
        }

        return $restantes <= 0;
    }

    public function disponible(string $fecha, ?string $espacioTipo, int $personas = 1): array
    {
        $categoria = $this->categoriaDeEspacioTipo($espacioTipo);

        if (! $categoria) {
            return [
                'ok'          => false,
                'disponible'  => false,
                'espacio'     => null,
                'tinaja'      => $this->slotsTinaja($fecha, $personas),
                'razon'       => 'El programa no tiene espacio_tipo configurado.',
            ];
        }

        $libres     = $this->ubicacionesLibresConCapacidad($fecha);
        $pendientes = $this->personasPendientesPorCategoria($fecha);

        if ($categoria === 'estacion') {
            $subTipos        = self::SUB_TIPOS_ESTACION;
            $libresCategoria = $libres->whereIn('sub_tipo', $subTipos);
            $libresEfectivas = $this->libresTrasReservarPendientes($libresCategoria, $pendientes['estacion']);
            $espacioOk       = $this->caben($personas, $libresEfectivas);
        } else {
            $subTipos  = self::SUB_TIPOS_WELLNESS;
            $terrazas  = $libres->where('sub_tipo', 'terraza');
            $reposeras = $libres->where('sub_tipo', 'reposera');
            $libresCategoria    = $terrazas->concat($reposeras);
            $terrazasEfectivas  = $this->libresTrasReservarPendientes($terrazas, $pendientes['wellness']);
            $reposerasEfectivas = $this->libresTrasReservarPendientes($reposeras, $pendientes['wellness']);
            $espacioOk          = $this->caben($personas, $terrazasEfectivas) || $this->caben($personas, $reposerasEfectivas);
        }

        $totalCategoria = DB::table('ubicaciones')
            ->where('activo', true)
            ->whereIn('sub_tipo', $subTipos)
            ->count();
        $libresCount = $libresCategoria->count();
        $usadosCount = $totalCategoria - $libresCount;

        $tinaja = $this->slotsTinaja($fecha, $personas);

        $disponible = $espacioOk && $tinaja['ok'];

        $razon = null;
        if (! $disponible) {
            $razon = ! $tinaja['ok'] && ! $espacioOk
                ? 'Sin cupo de tinaja ni de espacio para ese día.'
                : (! $tinaja['ok']
                    ? 'Los horarios de tinaja están completos para ese día.'
                    : 'No hay espacios disponibles para ese programa en ese día.');
        }

        return [
            'ok'         => true,
            'disponible' => $disponible,
            'categoria'  => $categoria,
            'espacio'    => [
                'tipo'   => $categoria,
                'usados' => $usadosCount,
                'max'    => $totalCategoria,
                'libres' => $libresCount,
                'ok'     => $espacioOk,
            ],
            'tinaja' => $tinaja,
            'razon'  => $razon,
        ];
    }
}
