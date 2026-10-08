<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * BotUbicacionesService
 *
 * Reglas de ubicación del bot (definidas por Sebastián, 08-10-2026):
 *  Wellness (espacio wellness/terraza/reposera):
 *   - 2 o 4 personas eligen terraza (Quillay) o reposera (reposeras de a par).
 *   - 3, 5 o 6 personas → un Quillay (se dejan Quillay 0 y 1 para grupos grandes).
 *   - 7 a 14 personas → Quillay 0 + Quillay 1 juntos.
 *  Programas con estación:
 *   - 1 a 3 → estación normal · 4 o 5 → un Níspero · 8 a 10 → los 2 Nísperos.
 *   - 6 o 7 → los 2 Nísperos si ningún otro grupo del día los necesita; si no, Níspero + estación.
 *   - Se optimiza por día: los grupos más grandes primero en Nísperos. Solo se reacomodan las
 *     asignaciones hechas por el sistema (reserva_ubicaciones.rol = 'auto'); lo asignado a mano
 *     por el equipo queda fijo.
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class BotUbicacionesService
{
    const ROL_AUTO = 'auto';
    const DIR_FOTOS = 'uploads/bot/ubicaciones';

    public static function tiposFoto(): array
    {
        return [
            'terraza'  => ['Terraza (Quillay)', 'Terraza privada techada, con vista a la cordillera.'],
            'reposera' => ['Reposera doble', 'Reposera doble acolchada, para dos personas.'],
            'estacion' => ['Estación de descanso', 'Estación de descanso con reposeras acolchadas, colchón, mantas y almohadas.'],
            'nispero'  => ['Níspero (estación grupal con terraza)', 'Estación grupal con terraza propia, para grupos.'],
        ];
    }

    public static function categoria($programa): ?string
    {
        $e = is_object($programa) ? ($programa->espacio_tipo ?? null) : null;
        if (in_array($e, ['wellness', 'terraza', 'reposera'], true)) {
            return 'wellness';
        }
        if (in_array($e, ['estacion_economico', 'estacion_intermedio', 'estacion_full'], true)) {
            return 'estacion';
        }
        return null;
    }

    // ───────────────────────── Fotos ─────────────────────────

    public static function fotoUrl(string $tipo): ?string
    {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $p = public_path(self::DIR_FOTOS . '/' . $tipo . '.' . $ext);
            if (is_file($p)) {
                return url(self::DIR_FOTOS . '/' . $tipo . '.' . $ext) . '?v=' . filemtime($p);
            }
        }
        return null;
    }

    public static function fotoTexto(string $tipo): string
    {
        $def = self::tiposFoto()[$tipo][1] ?? '';
        try {
            $v = DB::table('bot_secciones_texto')->where('clave', 'foto_texto_' . $tipo)->value('contenido');
        } catch (\Throwable $e) {
            $v = null;
        }
        return trim((string) $v) !== '' ? trim($v) : $def;
    }

    // ───────────────────────── Ocupación del día ─────────────────────────

    private static function unidades()
    {
        return DB::table('ubicaciones')->where('activo', true)->whereNotNull('sub_tipo')
            ->select('id', 'nombre', 'sub_tipo', 'capacidad_max')->orderBy('id')->get()->keyBy('id');
    }

    /** Devuelve [ids ocupados fijos, grupos auto de estación [reserva_id => personas]]. */
    private static function ocupacion(string $fecha, ?int $excluirReserva = null): array
    {
        $reservas = DB::table('reservas')
            ->whereRaw('DATE(fecha_visita) = ?', [$fecha])
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'cancelada');
            })
            ->when($excluirReserva, function ($q) use ($excluirReserva) {
                $q->where('id', '<>', $excluirReserva);
            })
            ->select('id', 'id_programa', 'cantidad_personas')->get()->keyBy('id');
        if ($reservas->isEmpty()) {
            return [[], []];
        }
        $ids = $reservas->keys()->all();
        $ru = DB::table('reserva_ubicaciones')->whereIn('id_reserva', $ids)->get()->groupBy('id_reserva');
        $vi = DB::table('visitas')->whereIn('id_reserva', $ids)->whereNotNull('id_ubicacion')->get()->groupBy('id_reserva');
        $programas = DB::table('programas')->whereIn('id', $reservas->pluck('id_programa')->unique()->all())->get()->keyBy('id');

        $fijas = [];
        $auto  = [];
        foreach ($reservas as $r) {
            $filas  = $ru->get($r->id, collect());
            $esAuto = $filas->isNotEmpty() && $filas->every(function ($f) {
                return $f->rol === self::ROL_AUTO;
            });
            $cat = self::categoria($programas->get($r->id_programa));
            if ($esAuto && $cat === 'estacion') {
                $auto[$r->id] = (int) $r->cantidad_personas;
                continue;
            }
            foreach ($filas as $f) {
                $fijas[] = (int) $f->id_ubicacion;
            }
            foreach ($vi->get($r->id, collect()) as $v) {
                $fijas[] = (int) $v->id_ubicacion;
            }
        }
        return [array_values(array_unique($fijas)), $auto];
    }

    // ───────────────────────── Reglas ─────────────────────────

    private static function repartir(int $personas, array $ids, $unidades): array
    {
        $out = [];
        $n = count($ids);
        foreach ($ids as $i => $id) {
            $cuota = (int) floor($personas / $n) + ($i < $personas % $n ? 1 : 0);
            $out[] = ['id' => (int) $id, 'personas' => $cuota];
        }
        return $out;
    }

    /**
     * Optimizador de estaciones (función pura, testeable).
     * $grupos: [clave => personas]; $nisperos / $estaciones: ids libres.
     * Devuelve [clave => [ids]] o null si algún grupo no cabe.
     */
    public static function optimizarEstaciones(array $grupos, array $nisperos, array $estaciones): ?array
    {
        arsort($grupos);
        $claves = array_keys($grupos);
        $N = array_values($nisperos);
        $E = array_values($estaciones);
        $res = [];
        foreach ($claves as $pos => $k) {
            $p = (int) $grupos[$k];
            $otrosGrandes = 0;
            foreach (array_slice($claves, $pos + 1) as $k2) {
                if ($grupos[$k2] >= 4) {
                    $otrosGrandes++;
                }
            }
            $asig = null;
            if ($p <= 3) {
                if ($E) {
                    $asig = [array_shift($E)];
                } elseif ($N) {
                    $asig = [array_shift($N)];
                }
            } elseif ($p <= 5) {
                if ($N) {
                    $asig = [array_shift($N)];
                } elseif (count($E) >= 2) {
                    $asig = [array_shift($E), array_shift($E)];
                }
            } elseif ($p <= 7) {
                if (count($N) >= 2 && $otrosGrandes === 0) {
                    $asig = [array_shift($N), array_shift($N)];
                } elseif (count($N) >= 1 && count($E) >= 1) {
                    $asig = [array_shift($N), array_shift($E)];
                } elseif (count($N) >= 2) {
                    $asig = [array_shift($N), array_shift($N)];
                } elseif (count($E) >= (int) ceil($p / 3)) {
                    $asig = array_splice($E, 0, (int) ceil($p / 3));
                }
            } else {
                if (count($N) >= 2 && $p <= 10) {
                    $asig = [array_shift($N), array_shift($N)];
                } elseif (count($N) >= 1 && count($E) >= (int) ceil(($p - 5) / 3)) {
                    $asig = array_merge([array_shift($N)], array_splice($E, 0, (int) ceil(($p - 5) / 3)));
                } elseif (count($E) >= (int) ceil($p / 3)) {
                    $asig = array_splice($E, 0, (int) ceil($p / 3));
                }
            }
            if ($asig === null) {
                return null;
            }
            $res[$k] = $asig;
        }
        return $res;
    }

    public static function textoEstacion(int $p): string
    {
        if ($p <= 3) {
            return 'una estación de descanso';
        }
        if ($p <= 5) {
            return 'una estación grupal con terraza (Níspero)';
        }
        if ($p <= 7) {
            return 'estación grupal; la distribución exacta (Níspero y estación) la confirma el equipo los días previos';
        }
        return '2 estaciones con 2 terrazas (los dos Nísperos)';
    }

    /**
     * Plan de ubicación para una reserva (nueva o existente con $reservaId).
     * $preferencia: 'terraza' | 'reposera' | null.
     */
    public static function plan(string $fecha, int $programaId, int $personas, ?string $preferencia = null, ?int $reservaId = null): array
    {
        $programa = DB::table('programas')->where('id', $programaId)->first();
        $cat = self::categoria($programa);
        $base = ['ok' => true, 'categoria' => $cat, 'requiere_eleccion' => false, 'opciones' => [], 'asignacion' => [],
                 'wellness' => null, 'tipo_foto' => null, 'texto_cliente' => null, 'reasignar' => [], 'motivo' => null];
        if (!$cat || $personas < 1) {
            return $base;
        }
        $unidades = self::unidades();
        list($fijas, $auto) = self::ocupacion($fecha, $reservaId);

        if ($cat === 'wellness') {
            $libres = $unidades->reject(function ($u) use ($fijas) {
                return in_array((int) $u->id, $fijas, true);
            });
            $grandes = $unidades->filter(function ($u) {
                return $u->sub_tipo === 'terraza' && preg_match('/quillay\s*[01]\b/i', $u->nombre);
            })->keys()->map(function ($i) { return (int) $i; })->all();
            $terrazasLibres = $libres->where('sub_tipo', 'terraza')->keys()->map(function ($i) { return (int) $i; })->all();
            $reposLibres    = $libres->where('sub_tipo', 'reposera')->keys()->map(function ($i) { return (int) $i; })->all();

            $planT = null;
            if ($personas <= 6) {
                $chicas = array_values(array_diff($terrazasLibres, $grandes));
                $orden  = array_merge($chicas, array_values(array_intersect($terrazasLibres, $grandes)));
                if ($orden) {
                    $planT = [['id' => $orden[0], 'personas' => $personas]];
                }
            } elseif ($personas <= 14 && count($grandes) === 2 && !array_diff($grandes, $terrazasLibres)) {
                $planT = self::repartir($personas, $grandes, $unidades);
            }
            $planR = null;
            if (in_array($personas, [2, 4], true) && count($reposLibres) >= $personas / 2) {
                $planR = self::repartir($personas, array_slice($reposLibres, 0, (int) ($personas / 2)), $unidades);
            }

            $base['opciones'] = ['terraza' => (bool) $planT, 'reposera' => (bool) $planR];
            if (in_array($personas, [2, 4], true) && $planT && $planR && !in_array($preferencia, ['terraza', 'reposera'], true)) {
                $base['requiere_eleccion'] = true;
                $base['texto_cliente'] = 'Pueden elegir entre terraza privada (Quillay) o ' . ($personas === 4 ? 'dos reposeras dobles' : 'una reposera doble') . '.';
                return $base;
            }
            $tipo = null;
            if ($preferencia === 'reposera' && $planR) {
                $tipo = 'reposera';
            } elseif ($preferencia === 'terraza' && $planT) {
                $tipo = 'terraza';
            } elseif ($planT) {
                $tipo = 'terraza';
            } elseif ($planR) {
                $tipo = 'reposera';
            }
            if (!$tipo) {
                $base['ok'] = false;
                $base['motivo'] = $personas > 6
                    ? 'Quillay 0 y Quillay 1 (terraza grande) no están libres juntos ese día.'
                    : 'No quedan terrazas ni reposeras libres para ese grupo ese día.';
                return $base;
            }
            $base['asignacion'] = $tipo === 'terraza' ? $planT : $planR;
            $base['wellness']   = $tipo;
            $base['tipo_foto']  = $tipo;
            $base['texto_cliente'] = $tipo === 'reposera'
                ? ($personas === 4 ? 'dos reposeras dobles' : 'una reposera doble')
                : ($personas > 6 ? 'la terraza grande (Quillay 0 y Quillay 1 juntos)' : 'una terraza privada (Quillay)');
            if ($preferencia && $preferencia !== $tipo) {
                $base['texto_cliente'] .= ' (la opción ' . $preferencia . ' ya no está disponible ese día)';
            }
            return $base;
        }

        // Estación
        $libres = $unidades->reject(function ($u) use ($fijas) {
            return in_array((int) $u->id, $fijas, true);
        });
        $N = $libres->where('sub_tipo', 'estacion_grupal')->keys()->map(function ($i) { return (int) $i; })->all();
        $E = $libres->where('sub_tipo', 'estacion_normal')->keys()->map(function ($i) { return (int) $i; })->all();
        $grupos = $auto;
        $grupos['nuevo'] = $personas;
        $opt = self::optimizarEstaciones($grupos, $N, $E);
        if ($opt === null) {
            // Sin reacomodar a los demás: solo lo que queda libre.
            $actuales = DB::table('reserva_ubicaciones')->whereIn('id_reserva', array_keys($auto) ?: [0])->pluck('id_ubicacion')->map(function ($i) { return (int) $i; })->all();
            $opt2 = self::optimizarEstaciones(['nuevo' => $personas], array_values(array_diff($N, $actuales)), array_values(array_diff($E, $actuales)));
            if ($opt2 === null) {
                $base['ok'] = false;
                $base['motivo'] = 'No quedan estaciones o Nísperos libres para ese grupo ese día.';
                return $base;
            }
            $opt = $opt2;
        }
        $base['asignacion'] = self::repartir($personas, $opt['nuevo'], $unidades);
        $tieneN = (bool) array_intersect($opt['nuevo'], $N);
        $base['tipo_foto'] = $tieneN ? 'nispero' : 'estacion';
        $base['texto_cliente'] = self::textoEstacion($personas);
        foreach ($opt as $k => $ids) {
            if ($k === 'nuevo') {
                continue;
            }
            $actual = DB::table('reserva_ubicaciones')->where('id_reserva', $k)->orderBy('id')->pluck('id_ubicacion')->map(function ($i) { return (int) $i; })->all();
            $nuevo = $ids;
            sort($actual);
            $cmp = $nuevo;
            sort($cmp);
            if ($actual !== $cmp) {
                $base['reasignar'][$k] = self::repartir((int) $auto[$k], $nuevo, $unidades);
            }
        }
        return $base;
    }

    /**
     * Marca como automáticas las ubicaciones de una reserva recién creada por el bot
     * (si la visita no alcanzó a guardarlas, las inserta) y aplica reacomodos del día.
     */
    public static function registrar(int $reservaId, array $plan)
    {
        try {
            if (empty($plan['asignacion'])) {
                return;
            }
            $existentes = DB::table('reserva_ubicaciones')->where('id_reserva', $reservaId)->count();
            if (!$existentes) {
                foreach ($plan['asignacion'] as $a) {
                    DB::table('reserva_ubicaciones')->insert([
                        'id_reserva' => $reservaId, 'id_ubicacion' => $a['id'], 'cantidad_personas' => $a['personas'],
                        'rol' => self::ROL_AUTO, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                DB::table('visitas')->where('id_reserva', $reservaId)->whereNull('id_ubicacion')
                    ->update(['id_ubicacion' => $plan['asignacion'][0]['id'], 'updated_at' => now()]);
            } else {
                DB::table('reserva_ubicaciones')->where('id_reserva', $reservaId)->update(['rol' => self::ROL_AUTO]);
            }
            try {
                if (!empty($plan['wellness']) && \Schema::hasTable('reserva_wellness')
                    && !DB::table('reserva_wellness')->where('id_reserva', $reservaId)->exists()) {
                    $fila = ['id_reserva' => $reservaId, 'tipo' => $plan['wellness']];
                    if (\Schema::hasColumn('reserva_wellness', 'created_at')) {
                        $fila['created_at'] = now();
                        $fila['updated_at'] = now();
                    }
                    DB::table('reserva_wellness')->insert($fila);
                }
            } catch (\Throwable $e) {
                \Log::warning('[BotUbicaciones] No se pudo registrar tipo wellness: ' . $e->getMessage());
            }
            foreach ($plan['reasignar'] ?? [] as $otra => $filas) {
                $previas = DB::table('reserva_ubicaciones')->where('id_reserva', $otra)->pluck('id_ubicacion')->all();
                if (DB::table('reserva_ubicaciones')->where('id_reserva', $otra)->where(function ($q) {
                    $q->whereNull('rol')->orWhere('rol', '<>', self::ROL_AUTO);
                })->exists()) {
                    continue; // el equipo la tocó: no se mueve
                }
                DB::table('reserva_ubicaciones')->where('id_reserva', $otra)->delete();
                foreach ($filas as $a) {
                    DB::table('reserva_ubicaciones')->insert([
                        'id_reserva' => $otra, 'id_ubicacion' => $a['id'], 'cantidad_personas' => $a['personas'],
                        'rol' => self::ROL_AUTO, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                DB::table('visitas')->where('id_reserva', $otra)
                    ->where(function ($q) use ($previas) {
                        $q->whereNull('id_ubicacion')->orWhereIn('id_ubicacion', $previas ?: [0]);
                    })
                    ->update(['id_ubicacion' => $filas[0]['id'], 'updated_at' => now()]);
                \Log::info('[BotUbicaciones] Reacomodo de ubicaciones', ['reserva' => $otra, 'nuevas' => $filas]);
            }
        } catch (\Throwable $e) {
            \Log::error('[BotUbicaciones] Error registrando ubicaciones: ' . $e->getMessage(), ['reserva' => $reservaId]);
        }
    }

    /** Payload para VisitaController@store (mismo formato que el formulario del backoffice). */
    public static function payloadVisita(array $plan): array
    {
        $p = ['trago_cortesia' => 'No'];
        if (!empty($plan['asignacion'])) {
            $p['ubicaciones'] = array_map(function ($a) {
                return ['id' => $a['id'], 'personas' => $a['personas']];
            }, $plan['asignacion']);
        }
        if (!empty($plan['wellness'])) {
            $p['wellness'] = $plan['wellness'];
        }
        return $p;
    }
}
