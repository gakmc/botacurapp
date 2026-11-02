<?php

namespace App\Services;

use App\Reserva;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Plan del dia siguiente: asistentes, programas, masajes, comidas, turno sugerido de
 * atencion al cliente y margen estimado. Tambien el resultado del mes a la fecha.
 * 17/10/2026. Parametros en config/turnos.php.
 */
class TurnosService
{
    private static function cfg($k, $def = null)
    {
        return config('turnos.' . $k, $def);
    }

    private static function cantidadPor(array $tabla, int $personas): int
    {
        ksort($tabla);
        foreach ($tabla as $hasta => $n) {
            if ($personas <= $hasta) {
                return (int) $n;
            }
        }
        return (int) end($tabla);
    }

    private static function salario(string $nombre, int $defecto = 45000): int
    {
        static $cache = [];
        if (! array_key_exists($nombre, $cache)) {
            try {
                $u = User::where('name', 'like', trim($nombre) . '%')->first();
                $cache[$nombre] = $u ? ['id' => $u->id, 'salario' => (int) ($u->salario ?: $defecto)] : ['id' => null, 'salario' => $defecto];
            } catch (\Throwable $e) {
                $cache[$nombre] = ['id' => null, 'salario' => $defecto];
            }
        }
        return $cache[$nombre]['salario'];
    }

    private static function userId(string $nombre): ?int
    {
        try {
            $u = User::where('name', 'like', trim($nombre) . '%')->value('id');
            return $u ? (int) $u : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Pago al masoterapeuta por un masaje (segun tipo y duracion). */
    public static function pagoMasaje($masaje): int
    {
        static $mapa = null;
        if ($mapa === null) {
            $mapa = [];
            if (Schema::hasTable('precios_tipos_masajes')) {
                foreach (DB::table('precios_tipos_masajes as p')->join('tipos_masajes as t', 't.id', '=', 'p.id_tipo_masaje')
                    ->whereNotNull('p.pago_masoterapeuta')->get(['t.nombre', 'p.duracion_minutos', 'p.pago_masoterapeuta']) as $p) {
                    $mapa[mb_strtolower(trim($p->nombre)) . '|' . (int) $p->duracion_minutos] = (int) $p->pago_masoterapeuta;
                }
            }
        }
        $dur = $masaje->tiempo_extra ? 60 : 30;
        $k = mb_strtolower(trim((string) $masaje->tipo_masaje)) . '|' . $dur;
        $base = (int) self::cfg('pago_masaje_defecto', 10000);
        return $mapa[$k] ?? ($dur === 60 ? $base * 2 : $base);
    }

    private static function reservasDe(string $fecha)
    {
        $q = Reserva::with(['cliente', 'programa.servicios', 'masajes', 'menus', 'desayunoOnce'])->whereDate('fecha_visita', $fecha);
        if (Schema::hasColumn('reservas', 'estado')) {
            $q->where(function ($w) {
                $w->whereNull('estado')->orWhereNotIn('estado', ['cancelada', 'no_show']);
            });
        }
        return $q->get();
    }

    /** Valor de programas de una reserva (con IVA). */
    public static function valorProgramas(Reserva $r): int
    {
        $total = 0;
        foreach ($r->detalleProgramas() as $p) {
            $v = $p->valor_unitario ?? optional($p->programa)->valor_programa;
            $total += (int) $v * (int) $p->personas;
        }
        return $total;
    }

    /** Elige el personal de atencion para la fecha, repartiendo entre quienes llevan menos dias. */
    public static function elegirAtencion(Carbon $f, int $cantidad): array
    {
        $dia = (int) $f->dayOfWeekIso;
        $disponibles = collect(self::cfg('atencion', []))->filter(function ($p) use ($dia, $f) {
            return in_array($dia, $p['dias'] ?? [], true) && (empty($p['hasta']) || $f->toDateString() <= $p['hasta']);
        })->values();

        $ini = $f->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $fin = $f->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
        $hay = Schema::hasTable('turnos_sugeridos');
        $orden = $disponibles->map(function ($p) use ($ini, $fin, $f, $hay) {
            $semana = $hay ? DB::table('turnos_sugeridos')->where('nombre', $p['nombre'])->whereBetween('fecha', [$ini, $fin])->where('fecha', '<>', $f->toDateString())->count() : 0;
            $quince = $hay ? DB::table('turnos_sugeridos')->where('nombre', $p['nombre'])->where('fecha', '>=', $f->copy()->subDays(14)->toDateString())->where('fecha', '<', $f->toDateString())->count() : 0;
            return $p + ['semana' => $semana, 'quince' => $quince, 'salario' => self::salario($p['nombre'])];
        })->sortBy(function ($p) {
            return sprintf('%02d-%02d-%s', $p['semana'], $p['quince'], $p['nombre']);
        })->values();

        return [
            'elegidos'    => $orden->take($cantidad)->values()->all(),
            'disponibles' => $orden->count(),
            'falta'       => max(0, $cantidad - $orden->count()),
        ];
    }

    /** Costos fijos del dia de la semana. */
    public static function fijos(Carbon $f): array
    {
        $out = [];
        foreach (self::cfg('fijos.' . $f->dayOfWeekIso, []) as $x) {
            if (! empty($x[2]) && $f->toDateString() > $x[2]) {
                continue;
            }
            $out[] = ['nombre' => $x[0], 'monto' => (int) $x[1]];
        }
        return $out;
    }

    /** Plan completo de una fecha. */
    public static function plan(string $fecha): array
    {
        $f = Carbon::parse($fecha);
        $reservas = self::reservasDe($f->toDateString());
        $personas = (int) $reservas->sum('cantidad_personas');

        $programas = [];
        $ingresoProg = 0;
        $desayunos = 0;
        $onces = 0;
        $masajes = collect();
        $menus = 0;
        $detalle = [];
        foreach ($reservas as $r) {
            foreach ($r->detalleProgramas() as $p) {
                $n = optional($p->programa)->nombre_programa ?: 'Sin programa';
                $programas[$n] = ($programas[$n] ?? 0) + (int) $p->personas;
            }
            $ingresoProg += self::valorProgramas($r);
            foreach ($r->desayunoOnce as $d) {
                if ($d->tipo === 'desayuno') {
                    $desayunos += $r->personasDesayunoOnce();
                } elseif ($d->tipo === 'once') {
                    $onces += $r->personasDesayunoOnce();
                }
            }
            $masajes = $masajes->merge($r->masajes);
            $menus += $r->menus->count();
            $detalle[] = [
                'id'       => $r->id,
                'cliente'  => optional($r->cliente)->nombre_cliente,
                'personas' => (int) $r->cantidad_personas,
                'programa' => $r->programas_texto,
                'estado'   => $r->estado ?? null,
                'masajes'  => $r->masajes->map(function ($m) {
                    return ($m->horario_masaje ?: 'sin hora') . ' ' . ($m->tipo_masaje ?: '') . ($m->tiempo_extra ? ' (60)' : '');
                })->values()->all(),
                'obs'      => $r->observacion,
            ];
        }
        arsort($programas);

        $nAtencion = $personas > 0 ? self::cantidadPor(self::cfg('atencion_por_personas', []), $personas) : 0;
        $nCocina = $personas > 0 ? self::cantidadPor(self::cfg('cocina_por_personas', []), $personas) : 0;
        $atencion = self::elegirAtencion($f, $nAtencion);

        $iva = (float) self::cfg('iva', 0.19);
        $consumoEst = $personas * (int) self::cfg('consumo_por_persona', 12500);
        $bruto = $ingresoProg + $consumoEst;
        $neto = (int) round($bruto / (1 + $iva));
        $costoAtencion = (int) collect($atencion['elegidos'])->sum('salario');
        $costoCocina = $nCocina * (int) self::cfg('salario_cocina', 45000);
        $costoMasajes = (int) $masajes->sum(function ($m) {
            return self::pagoMasaje($m);
        });
        $fijos = self::fijos($f);
        $costoFijos = (int) collect($fijos)->sum('monto');
        $insumos = (int) round($neto * (int) self::cfg('insumos_pct', 15) / 100);
        $costo = $costoAtencion + $costoCocina + $costoMasajes + $costoFijos + $insumos;
        $margen = $neto > 0 ? round(100 * ($neto - $costo) / $neto) : null;

        $horas = $masajes->pluck('horario_masaje')->filter()->sort()->values();

        return [
            'fecha'      => $f->toDateString(),
            'etiqueta'   => $f->locale('es')->isoFormat('dddd D/MM'),
            'reservas'   => $reservas->count(),
            'personas'   => $personas,
            'programas'  => $programas,
            'masajes'    => $masajes->count(),
            'masajes_desde' => $horas->first(),
            'masajes_hasta' => $horas->last(),
            'almuerzos'  => $menus ?: $personas,
            'desayunos'  => $desayunos,
            'onces'      => $onces,
            'detalle'    => $detalle,
            'n_atencion' => $nAtencion,
            'atencion'   => $atencion,
            'n_cocina'   => $nCocina,
            'horario'    => self::cfg('horario', '09:30 a 19:30'),
            'ingreso_bruto' => $bruto,
            'ingreso_neto'  => $neto,
            'consumo_est'   => $consumoEst,
            'costos' => [
                'atencion' => $costoAtencion, 'cocina' => $costoCocina, 'masajes' => $costoMasajes,
                'fijos' => $costoFijos, 'insumos' => $insumos, 'total' => $costo,
            ],
            'fijos'  => $fijos,
            'margen' => $margen,
            'meta'   => (int) self::cfg('margen_meta', 40),
        ];
    }

    /** Resultado del mes a la fecha (dias ya trabajados), con sueldos reales. */
    public static function mesALaFecha(?string $hasta = null): array
    {
        $hasta = Carbon::parse($hasta ?: now()->toDateString());
        $desde = $hasta->copy()->startOfMonth();
        $iva = (float) self::cfg('iva', 0.19);
        $bruto = 0;
        $masajes = 0;
        $personas = 0;
        $reservas = Reserva::with(['programa', 'masajes', 'venta.consumo'])
            ->whereDate('fecha_visita', '>=', $desde->toDateString())->whereDate('fecha_visita', '<=', $hasta->toDateString())
            ->when(Schema::hasColumn('reservas', 'estado'), function ($q) {
                $q->where(function ($w) {
                    $w->whereNull('estado')->orWhereNotIn('estado', ['cancelada', 'no_show']);
                });
            })->get();
        foreach ($reservas as $r) {
            $bruto += self::valorProgramas($r) + (int) optional(optional($r->venta)->consumo)->subtotal;
            $masajes += (int) $r->masajes->sum(function ($m) {
                return self::pagoMasaje($m);
            });
            $personas += (int) $r->cantidad_personas;
        }
        $neto = (int) round($bruto / (1 + $iva));
        $sueldos = (int) DB::table('sueldos')->whereBetween('dia_trabajado', [$desde->toDateString(), $hasta->toDateString()])->sum('valor_dia');
        // Dias trabajados cuyos sueldos aun no se cierran (el cierre es semanal): se estiman con el plan
        $estimados = 0;
        $diasPagados = DB::table('sueldos')->whereBetween('dia_trabajado', [$desde->toDateString(), $hasta->toDateString()])
            ->pluck('dia_trabajado')->map(function ($d) { return substr((string) $d, 0, 10); })->unique()->all();
        foreach ($reservas->groupBy(function ($r) { return Carbon::parse($r->getOriginal('fecha_visita'))->toDateString(); }) as $dia => $grupo) {
            if (! in_array($dia, $diasPagados, true)) {
                $pl = self::plan($dia);
                $estimados += $pl['costos']['atencion'] + $pl['costos']['cocina'] + $pl['costos']['fijos'];
            }
        }
        $sueldos += $estimados;
        $insumos = (int) round($neto * (int) self::cfg('insumos_pct', 15) / 100);
        $resultado = $neto - $sueldos - $masajes - $insumos;
        return [
            'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString(),
            'personas' => $personas, 'bruto' => $bruto, 'neto' => $neto, 'iva' => $bruto - $neto,
            'sueldos' => $sueldos, 'sueldos_estimados' => $estimados, 'masajes' => $masajes, 'insumos' => $insumos,
            'resultado' => $resultado, 'margen' => $neto > 0 ? round(100 * $resultado / $neto) : null,
        ];
    }

    /** Guarda el turno sugerido (para repartir los dias y, despues, avisar a cada persona). */
    public static function guardarSugerencia(array $plan): void
    {
        if (! Schema::hasTable('turnos_sugeridos')) {
            return;
        }
        DB::table('turnos_sugeridos')->where('fecha', $plan['fecha'])->delete();
        foreach ($plan['atencion']['elegidos'] as $p) {
            DB::table('turnos_sugeridos')->insert([
                'fecha' => $plan['fecha'], 'nombre' => $p['nombre'], 'user_id' => self::userId($p['nombre']),
                'area' => 'atencion', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
