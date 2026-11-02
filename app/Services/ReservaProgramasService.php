<?php

namespace App\Services;

use App\Masaje;
use App\Menu;
use App\Programa;
use App\Reserva;
use App\ReservaPrograma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Reservas con mas de un programa y ajuste de menus/masajes a la cantidad de personas.
 * 15/10/2026.
 */
class ReservaProgramasService
{
    /** Programas adicionales del formulario: [[id_programa, personas], ...] validos. */
    public static function extrasDesdeRequest(Request $request): array
    {
        $out = [];
        foreach ((array) $request->input('programas_extra', []) as $fila) {
            if (! is_array($fila)) {
                continue;
            }
            $id = (int) ($fila['id_programa'] ?? 0);
            $personas = (int) preg_replace('/\D/', '', (string) ($fila['personas'] ?? ''));
            if ($id > 0 && $personas > 0 && Programa::whereKey($id)->exists()) {
                $out[] = ['id_programa' => $id, 'personas' => $personas];
            }
        }
        return $out;
    }

    /** Programas adicionales guardados (sin el principal), para comparar con el formulario. */
    public static function extrasGuardados(Reserva $reserva): array
    {
        if (! Schema::hasTable('reserva_programas')) {
            return [];
        }
        return ReservaPrograma::where('id_reserva', $reserva->id)->where('principal', false)->orderBy('id')->get()
            ->map(function ($f) {
                return ['id_programa' => (int) $f->id_programa, 'personas' => (int) $f->cantidad_personas];
            })->values()->all();
    }

    /**
     * Guarda los programas de la reserva y deja reservas.cantidad_personas = total y
     * cantidad_masajes = personas de programas con masaje. Devuelve el monto de programas
     * (suma de valor por persona x personas).
     * $valorPrincipal: precio por persona del programa principal (si se quiere respetar el original).
     */
    public static function guardar(Reserva $reserva, int $idPrincipal, int $personasPrincipal, array $extras, ?float $valorPrincipal = null): int
    {
        $previos = ReservaPrograma::where('id_reserva', $reserva->id)->get()->keyBy('id_programa');

        // Agrupar por programa (si repiten el principal en adicionales, se suma)
        $filas = [$idPrincipal => ['personas' => $personasPrincipal, 'principal' => true]];
        foreach ($extras as $e) {
            if (isset($filas[$e['id_programa']])) {
                $filas[$e['id_programa']]['personas'] += $e['personas'];
            } else {
                $filas[$e['id_programa']] = ['personas' => $e['personas'], 'principal' => false];
            }
        }

        $programas = Programa::with('servicios')->whereIn('id', array_keys($filas))->get()->keyBy('id');
        $monto = 0;
        $total = 0;
        $conMasaje = 0;
        $guardar = [];
        foreach ($filas as $id => $f) {
            $prog = $programas[$id] ?? null;
            if ($f['principal'] && $valorPrincipal) {
                $valor = $valorPrincipal;
            } elseif (isset($previos[$id]) && $previos[$id]->valor_unitario !== null) {
                $valor = (float) $previos[$id]->valor_unitario;
            } else {
                $valor = (float) optional($prog)->valor_programa;
            }
            $monto += (int) round($valor * $f['personas']);
            $total += $f['personas'];
            if ($prog && $prog->incluye_masajes) {
                $conMasaje += $f['personas'];
            }
            $guardar[] = [
                'id_reserva'        => $reserva->id,
                'id_programa'       => $id,
                'cantidad_personas' => $f['personas'],
                'valor_unitario'    => (int) round($valor),
                'principal'         => $f['principal'],
            ];
        }

        ReservaPrograma::where('id_reserva', $reserva->id)->delete();
        if (count($guardar) > 1) {
            foreach ($guardar as $g) {
                ReservaPrograma::create($g);
            }
        }

        $reserva->forceFill([
            'id_programa'       => $idPrincipal,
            'cantidad_personas' => $total,
            'cantidad_masajes'  => $conMasaje > 0 ? $conMasaje : null,
        ])->save();

        return $monto;
    }

    /**
     * Deja menus y masajes de la reserva acordes a la cantidad de personas actual
     * (por ejemplo, si pasa de 16 a 12 personas). Solo toca lo que ya fue creado:
     * si la reserva aun no tiene menus/masajes, los crea el registro de la visita.
     */
    public static function ajustarMenusYMasajes(Reserva $reserva): array
    {
        $reserva->refresh();
        $personas = max(0, (int) $reserva->cantidad_personas);
        $res = ['menus' => null, 'masajes' => null];

        // ---- Menus: uno por persona ----
        $menus = Menu::where('id_reserva', $reserva->id)->orderBy('id')->get();
        if ($menus->count() > 0 && $menus->count() !== $personas) {
            if ($menus->count() < $personas) {
                for ($i = $menus->count(); $i < $personas; $i++) {
                    Menu::create([
                        'id_reserva'                 => $reserva->id,
                        'id_producto_entrada'        => null,
                        'id_producto_fondo'          => null,
                        'id_producto_acompanamiento' => null,
                        'alergias'                   => null,
                        'observacion'                => null,
                    ]);
                }
            } else {
                $sobran = $menus->count() - $personas;
                // Primero los que no tienen nada elegido, desde el ultimo
                $vacios = $menus->filter(function ($m) {
                    return ! $m->id_producto_entrada && ! $m->id_producto_fondo && ! $m->id_producto_acompanamiento
                        && ! $m->alergias && ! $m->observacion;
                })->reverse();
                $borrar = $vacios->take($sobran)->pluck('id')->all();
                if (count($borrar) < $sobran) {
                    $resto = $menus->reverse()->whereNotIn('id', $borrar)->take($sobran - count($borrar))->pluck('id')->all();
                    $borrar = array_merge($borrar, $resto);
                }
                Menu::whereIn('id', $borrar)->delete();
            }
            $res['menus'] = $personas;
        }

        // ---- Masajes incluidos en el programa: uno por persona con masaje ----
        $conMasaje = $reserva->personasConMasaje();
        $masajes = Masaje::where('id_reserva', $reserva->id)->orderBy('persona')->get();
        if ($conMasaje > 0 && $masajes->count() > 0) {
            $existentes = $masajes->keyBy(function ($m) {
                return (int) $m->persona;
            });
            for ($p = 1; $p <= $conMasaje; $p++) {
                if (! isset($existentes[$p])) {
                    Masaje::create([
                        'horario_masaje'  => null,
                        'tipo_masaje'     => 'Relajación',
                        'id_lugar_masaje' => 1,
                        'persona'         => $p,
                        'tiempo_extra'    => 0,
                        'id_reserva'      => $reserva->id,
                        'user_id'         => null,
                    ]);
                }
            }
            Masaje::where('id_reserva', $reserva->id)->where('persona', '>', $conMasaje)->delete();
            $res['masajes'] = $conMasaje;
        } elseif ($conMasaje === 0 && $masajes->count() > $personas && $personas > 0) {
            // Solo masajes extra: no puede haber mas masajes que personas
            Masaje::where('id_reserva', $reserva->id)->where('persona', '>', $personas)->delete();
            $res['masajes'] = $personas;
        }

        return $res;
    }
}
