<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\BotController;
use App\Services\BotTarifasService as T;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * php artisan bot:probar            → reglas de cobro + conversaciones de prueba (simulación)
 * php artisan bot:probar --reglas   → solo reglas de cobro (sin IA, instantáneo)
 *
 * Nada se envía por WhatsApp, no se crean reservas ni holds. Las conversaciones
 * usan teléfonos ficticios 569000001NN y se borran al terminar.
 */
class BotProbar extends Command
{
    protected $signature = 'bot:probar {--reglas : Solo pruebas de cobros y reglas, sin IA}';
    protected $description = 'Batería de pruebas del bot de WhatsApp (simulación, no envía nada)';

    private $ok = 0;
    private $fallas = 0;
    private $revisar = 0;
    private $log = '';

    public function handle()
    {
        $this->linea('== PRUEBAS DE COBROS Y REGLAS (' . Carbon::now('America/Santiago')->format('Y-m-d H:i') . ') ==');
        $this->pruebasReglas();
        if (!$this->option('reglas')) {
            $this->linea('');
            $this->linea('== CONVERSACIONES DE PRUEBA (simulación con IA) ==');
            $this->pruebasConversacion();
        }
        $this->linea('');
        $this->linea("RESULTADO: {$this->ok} OK · {$this->fallas} FALLAS · {$this->revisar} PARA REVISAR");
        $archivo = storage_path('logs/bot_pruebas_' . date('Ymd_His') . '.txt');
        file_put_contents($archivo, $this->log);
        $this->line('Detalle completo guardado en ' . $archivo);
        return $this->fallas > 0 ? 1 : 0;
    }

    private function linea($t)
    {
        $this->line($t);
        $this->log .= $t . "\n";
    }

    private function check($nombre, $cond, $detalle = '')
    {
        if ($cond) {
            $this->ok++;
            $this->linea('  OK     ' . $nombre);
        } else {
            $this->fallas++;
            $this->linea('  FALLA  ' . $nombre . ($detalle ? ' → ' . $detalle : ''));
        }
    }

    private function codigos(array $cot)
    {
        return array_column($cot['errores'], 'codigo');
    }

    private function proximo(int $diaIso, int $desdeDias = 3)
    {
        $f = Carbon::now('America/Santiago')->addDays($desdeDias);
        while ($f->dayOfWeekIso !== $diaIso) {
            $f->addDay();
        }
        return $f->toDateString();
    }

    private function pruebasReglas()
    {
        $p1   = DB::table('programas')->where('id', 1)->first();
        $p28  = DB::table('programas')->where('id', 28)->first();
        $ex   = T::preciosExtras();
        $pars = T::parametros();
        $abonoT  = (int) $pars['param_abono_transferencia_pct'];
        $pagoTj  = (int) $pars['param_pago_tarjeta_pct'];
        $recargo = (int) $pars['param_recargo_verano'];
        $this->linea('Parámetros: abono transferencia ' . $abonoT . '% · tarjeta ' . $pagoTj . '% · recargo verano $' . number_format($recargo, 0, ',', '.')
            . ' · masaje $' . number_format($ex['masaje'], 0, ',', '.') . ' · desayuno/once $' . number_format($ex['desayuno_once'], 0, ',', '.'));

        $anio = (int) date('Y');
        $oct  = ($anio) . '-10-24';
        $nov  = ($anio) . '-11-14';

        if ($p1) {
            $v = (int) $p1->valor_programa;
            $c = T::calcularMontos(1, $oct, 2, 0, 0, 'Transferencia');
            $this->check('Wellness Plus 2 personas octubre, transferencia: total ' . T::fmt(2 * $v), $c['total'] === 2 * $v, json_encode($c['total']));
            $this->check('  abono ' . $abonoT . '% = ' . T::fmt(ceil(2 * $v * $abonoT / 100)), $c['pago_ahora'] === (int) ceil(2 * $v * $abonoT / 100));
            $c = T::calcularMontos(1, $nov, 2, 0, 0, 'Transferencia');
            $this->check('Wellness Plus 2 personas noviembre: incluye recargo de verano', $c['total'] === 2 * ($v + $recargo), 'total ' . $c['total']);
            $c = T::calcularMontos(1, $oct, 2, 0, 0, 'Débito');
            $this->check('Pago con tarjeta: se cobra ' . $pagoTj . '% al reservar', $c['pago_ahora'] === (int) ceil(2 * $v * $pagoTj / 100), 'pago ' . $c['pago_ahora']);
        } else {
            $this->check('Programa 1 existe', false);
        }
        if ($p28) {
            $v = (int) $p28->valor_programa;
            $c = T::calcularMontos(28, $oct, 2, 1, 2, 'Transferencia');
            $esp = 2 * $v + $ex['masaje'] + 2 * $ex['desayuno_once'];
            $this->check('Relax Day 2p + 1 masaje + 2 desayunos: total ' . T::fmt($esp), $c['total'] === $esp, 'total ' . $c['total']);
        }
        $c = T::calcularMontos(3, $nov, 2, 0, 0, 'Transferencia');
        $esperado = (int) round($recargo * (100 - (int) $pars['param_descuento_semana_pct']) / 100);
        $this->check('Programa S en noviembre: recargo con el descuento aplicado (' . T::fmt($esperado) . ' por persona)', $c['recargo_verano_persona'] === $esperado, 'recargo ' . $c['recargo_verano_persona']);

        $sab  = $this->proximo(6);
        $jue  = $this->proximo(4);
        $lejos = Carbon::now('America/Santiago')->addMonths(2)->startOfMonth()->addDays(14)->toDateString();
        $this->check('Grupo de 12 personas → derivar a correo', in_array('grupo_grande', $this->codigos(T::cotizar(['programa_id' => 1, 'fecha' => $sab, 'personas' => 12]))));
        $this->check('Botacura Full (id 2) no disponible', in_array('programa_no_disponible', $this->codigos(T::cotizar(['programa_id' => 2, 'fecha' => $sab, 'personas' => 2]))));
        $fds = T::calcularMontos(48, $nov, 2, 0, 0, 'Transferencia');
        $fdsS = T::calcularMontos(3, $nov, 2, 0, 0, 'Transferencia');
        $this->linea('  INFO   Noviembre: Full Day ' . T::fmt($fds['valor_persona']) . ' / Full Day S ' . T::fmt($fdsS['valor_persona']) . ' por persona');
        $this->check('Programa S en sábado → rechazado', in_array('semana_solo_lunes_viernes', $this->codigos(T::cotizar(['programa_id' => 3, 'fecha' => $sab, 'personas' => 2]))));
        $this->check('Programa S pagado con tarjeta → rechazado', in_array('solo_transferencia', $this->codigos(T::cotizar(['programa_id' => 3, 'fecha' => $jue, 'personas' => 2, 'tipo_pago' => 'Débito']))));
        $this->check('Fecha de un mes con agenda cerrada → rechazada (' . $lejos . ')', in_array('agenda_no_abierta', $this->codigos(T::cotizar(['programa_id' => 1, 'fecha' => $lejos, 'personas' => 2]))));
        $this->check('Fecha pasada → rechazada', in_array('fecha_pasada', $this->codigos(T::cotizar(['programa_id' => 1, 'fecha' => Carbon::now('America/Santiago')->subDays(3)->toDateString(), 'personas' => 2]))));
        $this->pruebasUbicaciones();

        $M = \App\Services\BotMasajesService::class;
        list($ok) = $M::caben(array_fill(0, 17, 2), 17);
        $this->check('Masajes: 17 parejas caben en el container', $ok);
        list($ok) = $M::caben(array_merge(array_fill(0, 17, 2), [2]), 17);
        $this->check('Masajes: la pareja 18 ya no cabe', !$ok);
        list($ok, $c, $e) = $M::caben([3, 3], 17);
        $this->check('Masajes: 2 grupos de 3 → 2 turnos de container y 2 de enfermería', $ok && $c === 2 && $e === 2, "container {$c}, enfermería {$e}");
        $sab = $this->proximo(6);
        $d = $M::disponible($sab, 0);
        $this->linea('  INFO   Masajes el ' . $sab . ': container ' . $d['container_usados'] . '/' . $d['turnos_por_espacio'] . ' turnos, enfermería ' . $d['enfermeria_usados'] . '/' . $d['turnos_por_espacio']);

        $c = T::cotizar(['programa_id' => 1, 'fecha' => $sab, 'personas' => 2, 'tipo_pago' => 'Transferencia']);
        $this->linea('  INFO   Wellness Plus 2p el ' . $sab . ': ' . ($c['ok'] ? 'OK ' . $c['total_formato'] : 'no reservable: ' . implode(', ', $this->codigos($c))));
    }

    private function pruebasUbicaciones()
    {
        $U = \App\Services\BotUbicacionesService::class;
        $N = [8, 9];
        $E = [10, 11, 12, 13, 14, 15, 16];
        $o = $U::optimizarEstaciones(['a' => 6], $N, $E);
        $this->check('Estación: grupo de 6 solo en el día → 2 Nísperos', $o && $o['a'] === [8, 9], json_encode($o));
        $o = $U::optimizarEstaciones(['a' => 6, 'b' => 5], $N, $E);
        $this->check('Estación: 6 y 5 el mismo día → 6 en Níspero + estación, 5 en Níspero', $o && count(array_intersect($o['a'], $N)) === 1 && $o['b'] === [9], json_encode($o));
        $o = $U::optimizarEstaciones(['a' => 8, 'b' => 4], $N, $E);
        $this->check('Estación: 8 y 4 → el de 8 en los 2 Nísperos', $o && $o['a'] === [8, 9], json_encode($o));

        $f = Carbon::now('America/Santiago')->addDays(400)->toDateString(); // día sin reservas
        $p = $U::plan($f, 1, 2);
        $this->check('Wellness 2 personas → puede elegir terraza o reposera', $p['requiere_eleccion'] === true, json_encode($p['opciones']));
        $p = $U::plan($f, 1, 2, 'reposera');
        $this->check('Wellness 2 personas eligiendo reposera → 1 reposera', $p['ok'] && $p['wellness'] === 'reposera' && count($p['asignacion']) === 1, json_encode($p['asignacion']));
        $p = $U::plan($f, 1, 4, 'reposera');
        $this->check('Wellness 4 personas eligiendo reposera → 2 reposeras', $p['ok'] && count($p['asignacion']) === 2, json_encode($p['asignacion']));
        $p = $U::plan($f, 39, 3);
        $this->check('Wellness 3 personas → un Quillay (sin elección)', $p['ok'] && !$p['requiere_eleccion'] && $p['wellness'] === 'terraza' && count($p['asignacion']) === 1, json_encode($p['asignacion']));
        $p = $U::plan($f, 39, 8);
        $ids = array_column($p['asignacion'], 'id');
        sort($ids);
        $this->check('Wellness 8 personas → Quillay 0 y Quillay 1 juntos', $p['ok'] && $ids === [1, 2], json_encode($p['asignacion']));
        $p = $U::plan($f, 28, 2);
        $this->check('Relax Day 2 personas → estación', $p['ok'] && $p['tipo_foto'] === 'estacion', json_encode($p['asignacion']));
        $p = $U::plan($f, 28, 5);
        $this->check('Relax Day 5 personas → Níspero', $p['ok'] && $p['tipo_foto'] === 'nispero' && count($p['asignacion']) === 1, json_encode($p['asignacion']));
        $p = $U::plan($f, 48, 9);
        $this->check('Full Day 9 personas → 2 Nísperos', $p['ok'] && count($p['asignacion']) === 2, json_encode($p['asignacion']));
        foreach (array_keys($U::tiposFoto()) as $t) {
            $this->linea('  INFO   Foto ' . $t . ': ' . ($U::fotoUrl($t) ?: 'sin cargar'));
        }
    }

    private function pruebasConversacion()
    {
        $sab  = Carbon::parse($this->proximo(6, 10));
        $txtSab = $sab->locale('es')->isoFormat('dddd D [de] MMMM');
        $hoy = Carbon::now('America/Santiago');
        $mesCerrado = $hoy->day >= (int) T::parametros()['param_dia_apertura_agenda'] ? $hoy->copy()->addMonths(2) : $hoy->copy()->addMonth();
        $txtCerrado = 'sábado ' . $mesCerrado->copy()->startOfMonth()->addDays(14)->locale('es')->isoFormat('D [de] MMMM');

        $escenarios = [
            ['Precio Relax Day', ['Hola! cuánto sale el Relax Day para 2 personas?'], ['incluye' => [], 'acciones' => []]],
            ['Mes con agenda cerrada', ["Hola, tienen disponibilidad para 2 personas el {$txtCerrado}?"], ['incluye' => ['20']]],
            ['Grupo de 12', ['Hola, somos 12 personas de la oficina y queremos ir en un viernes'], ['incluye' => ['hola@botacura.cl']]],
            ['Programa S en fin de semana', ["Quiero el Full Day S para 2 personas el {$txtSab}"], ['incluye' => ['lunes a viernes']]],
            ['Reagendar sin reserva', ['Hola, necesito cambiar la fecha de mi reserva'], ['excluye' => ['quedó reagendada', 'reagendamos']]],
            ['Pide hablar con persona', ['Quiero hablar con una persona por favor'], ['accion' => 'escalar_humano']],
            ['Pide descuento', ['Si vamos 4 personas el Relax Day me hacen un 20% de descuento?'], ['excluye' => ['20% de descuento aplicado']]],
            ['Salud', ['Estoy embarazada de 5 meses, ¿puedo usar la tinaja?'], ['incluye' => ['médic']]],
            ['Niños', ['¿Puedo ir con mi hijo de 3 años?'], ['incluye' => ['4 años']]],
            ['Ubicación Wellness 2 personas', ["Hola, quiero el Wellness Plus para 2 personas el {$txtSab}"], ['incluye' => ['terraza', 'reposera'], 'acciones' => ['ubicaciones']]],
            ['Reserva completa por transferencia', [
                'Hola, quiero reservar para 2 personas',
                'El Wellness Plus',
                "El {$txtSab}",
                'No, ninguna ocasión especial',
                'Juan Prueba',
                'Sí, ese mismo número',
                'prueba@botacura.cl',
                'Sí, ya leí las políticas',
                'No, sin extras',
                'Transferencia',
                'Sí, confirmo',
            ], ['acciones' => ['cotizar']]],
        ];

        $n = 100;
        foreach ($escenarios as $esc) {
            list($titulo, $mensajes, $esperado) = $esc;
            $tel = '569000001' . str_pad((string) (++$n % 100), 2, '0', STR_PAD_LEFT);
            $this->limpiar($tel);
            $this->linea('');
            $this->linea('--- ' . $titulo . ' (' . $tel . ')');
            $acciones = [];
            $ultima = null;
            $todo = '';
            foreach ($mensajes as $m) {
                $req = Request::create('/api/bot-ai/message', 'POST', ['telefono' => $tel, 'mensaje' => $m, 'nombre' => 'Prueba', 'simulacion' => true]);
                $req->headers->set('X-Bot-Secret', (string) config('services.bot.secret'));
                try {
                    $res = json_decode(app(BotController::class)->message($req)->getContent(), true);
                } catch (\Throwable $e) {
                    $res = ['accion' => 'error', 'mensaje' => 'EXCEPCIÓN: ' . $e->getMessage()];
                }
                $ultima = $res;
                $acciones = array_merge($acciones, $res['acciones_ejecutadas'] ?? [], [$res['accion'] ?? '?']);
                $todo .= ' ' . ($res['mensaje'] ?? '');
                $this->linea('  Cliente: ' . $m);
                foreach ($res['imagenes'] ?? [] as $img) {
                    $this->linea('  Bot [foto]: ' . $img['url']);
                }
                $this->linea('  Bot [' . ($res['accion'] ?? '?') . (empty($res['acciones_ejecutadas']) ? '' : ' / sistema: ' . implode(',', $res['acciones_ejecutadas'])) . ']: ' . str_replace("\n", "\n       ", (string) ($res['mensaje'] ?? '')));
            }
            $low = mb_strtolower($todo);
            $fallo = [];
            foreach ($esperado['incluye'] ?? [] as $t) {
                if (mb_strpos($low, mb_strtolower($t)) === false) {
                    $fallo[] = 'no menciona "' . $t . '"';
                }
            }
            foreach ($esperado['excluye'] ?? [] as $t) {
                if (mb_strpos($low, mb_strtolower($t)) !== false) {
                    $fallo[] = 'dice "' . $t . '"';
                }
            }
            foreach ($esperado['acciones'] ?? [] as $a) {
                if (!in_array($a, $acciones, true)) {
                    $fallo[] = 'no usó la acción ' . $a;
                }
            }
            if (!empty($esperado['accion']) && !in_array($esperado['accion'], $acciones, true)) {
                $fallo[] = 'no usó ' . $esperado['accion'];
            }
            if (($ultima['accion'] ?? '') === 'error') {
                $fallo[] = 'error técnico';
            }
            if ($fallo) {
                $this->revisar++;
                $this->linea('  REVISAR: ' . implode('; ', $fallo));
            } else {
                $this->ok++;
                $this->linea('  OK');
            }
            $this->limpiar($tel);
        }
    }

    private function limpiar($tel)
    {
        DB::table('bot_conversaciones')->where('usuario_id', $tel)->delete();
        DB::table('bot_mensajes_pendientes')->where('telefono', $tel)->delete();
    }
}
