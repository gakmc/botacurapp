<?php

namespace App\Console\Commands;

use App\Services\TurnosService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Reporte del dia siguiente (17/10/2026): sale a las 15:00 solo si al dia siguiente hay reservas.
 * WhatsApp corto por el bot de sistema (Home Assistant) + correo con el detalle.
 *   php artisan botacura:reporte-manana --probar            (muestra sin enviar)
 *   php artisan botacura:reporte-manana --fecha=2026-10-24  (otra fecha)
 */
class ReporteManana extends Command
{
    protected $signature = 'botacura:reporte-manana {--fecha=} {--probar}';
    protected $description = 'Reporte del dia siguiente con turno sugerido y margen estimado';

    private function m($n)
    {
        return '$' . number_format((int) $n, 0, ',', '.');
    }

    public function handle()
    {
        $fecha = $this->option('fecha') ?: Carbon::now('America/Santiago')->addDay()->toDateString();
        $plan = TurnosService::plan($fecha);

        if ($plan['personas'] <= 0) {
            $this->info('Sin reservas para ' . $fecha . ': no se envia nada.');
            return 0;
        }

        $mes = TurnosService::mesALaFecha(Carbon::parse($fecha)->subDay()->toDateString());
        $corto = $this->textoCorto($plan, $mes);
        $largo = $this->textoLargo($plan, $mes);

        if ($this->option('probar')) {
            $this->line("WHATSAPP (" . mb_strlen($corto) . " caracteres):\n" . $corto . "\n");
            $this->line("CORREO:\n" . $largo);
            return 0;
        }

        TurnosService::guardarSugerencia($plan);
        $this->enviarWhatsapp($corto);
        $this->enviarCorreo($plan, $largo);
        $this->info('Reporte enviado para ' . $fecha);
        return 0;
    }

    private function textoCorto(array $p, array $mes): string
    {
        $progs = collect($p['programas'])->map(function ($n, $k) {
            return $n . ' ' . $k;
        })->implode(', ');
        $nombres = collect($p['atencion']['elegidos'])->map(function ($x) {
            return explode(' ', $x['nombre'])[0];
        })->implode(', ');
        $t = 'Mañana ' . $p['etiqueta'] . ': ' . $p['personas'] . ' personas en ' . $p['reservas'] . ' reserva' . ($p['reservas'] === 1 ? '' : 's')
            . ' · ' . $progs
            . ' · ' . $p['masajes'] . ' masajes' . ($p['masajes_desde'] ? ' (' . $p['masajes_desde'] . '-' . $p['masajes_hasta'] . ')' : '')
            . ' · ' . $p['almuerzos'] . ' almuerzos' . ($p['desayunos'] ? ', ' . $p['desayunos'] . ' desayunos' : '') . ($p['onces'] ? ', ' . $p['onces'] . ' onces' : '')
            . ' · Atención ' . $p['horario'] . ': ' . ($nombres ?: 'nadie disponible') . ($p['atencion']['falta'] ? ' (faltan ' . $p['atencion']['falta'] . ')' : '')
            . ' · Cocina: ' . $p['n_cocina']
            . ' · Margen estimado ' . ($p['margen'] === null ? '-' : $p['margen'] . '%') . ($p['margen'] !== null && $p['margen'] < $p['meta'] ? ' (bajo ' . $p['meta'] . '%)' : '')
            . ' · Mes: ' . ($mes['margen'] === null ? '-' : $mes['margen'] . '%') . ', IVA a separar ' . $this->m($mes['iva']);
        return mb_substr($t, 0, 650);
    }

    private function textoLargo(array $p, array $mes): string
    {
        $c = $p['costos'];
        $l = [];
        $l[] = 'REPORTE DE MAÑANA: ' . mb_strtoupper($p['etiqueta']);
        $l[] = '';
        $l[] = $p['personas'] . ' personas en ' . $p['reservas'] . ' reservas';
        foreach ($p['programas'] as $nombre => $n) {
            $l[] = '  - ' . $n . ' ' . $nombre;
        }
        $l[] = 'Masajes: ' . $p['masajes'] . ($p['masajes_desde'] ? ' (de ' . $p['masajes_desde'] . ' a ' . $p['masajes_hasta'] . ')' : '');
        $l[] = 'Almuerzos: ' . $p['almuerzos'] . ' · Desayunos: ' . $p['desayunos'] . ' · Onces: ' . $p['onces'];
        $l[] = '';
        $l[] = 'TURNO SUGERIDO (' . $p['horario'] . ')';
        $l[] = 'Atención al cliente (' . $p['n_atencion'] . '):';
        foreach ($p['atencion']['elegidos'] as $x) {
            $l[] = '  - ' . $x['nombre'] . ' (' . $x['semana'] . ' día(s) esta semana) ' . $this->m($x['salario']);
        }
        if ($p['atencion']['falta']) {
            $l[] = '  ! Faltan ' . $p['atencion']['falta'] . ' persona(s): no hay más disponibles ese día.';
        }
        $l[] = 'Cocina: ' . $p['n_cocina'] . ' persona(s)';
        $l[] = '';
        $l[] = 'MARGEN ESTIMADO DEL DÍA';
        $l[] = 'Ingreso con IVA: ' . $this->m($p['ingreso_bruto']) . ' (programas ' . $this->m($p['ingreso_bruto'] - $p['consumo_est']) . ' + consumo estimado ' . $this->m($p['consumo_est']) . ')';
        $l[] = 'Ingreso sin IVA: ' . $this->m($p['ingreso_neto']) . ' (IVA ' . $this->m($p['ingreso_bruto'] - $p['ingreso_neto']) . ')';
        $l[] = 'Atención ' . $this->m($c['atencion']) . ' · Cocina ' . $this->m($c['cocina']) . ' · Masajes ' . $this->m($c['masajes'])
            . ' · Fijos ' . $this->m($c['fijos']) . ' · Insumos ' . $this->m($c['insumos']) . ' (' . config('turnos.insumos_pct') . '%)';
        $l[] = 'Costo total: ' . $this->m($c['total']) . ' → margen ' . ($p['margen'] === null ? '-' : $p['margen'] . '%') . ' (meta ' . $p['meta'] . '%)';
        if ($p['margen'] !== null && $p['margen'] < $p['meta']) {
            $l[] = '! Bajo la meta: revisar si se necesita todo el personal fijo ese día (' . collect($p['fijos'])->pluck('nombre')->implode(', ') . ').';
        }
        $l[] = '';
        $l[] = 'MES A LA FECHA (' . Carbon::parse($mes['desde'])->format('d/m') . ' al ' . Carbon::parse($mes['hasta'])->format('d/m') . ')';
        $l[] = 'Ingreso sin IVA ' . $this->m($mes['neto']) . ' · Sueldos ' . $this->m($mes['sueldos'])
            . ($mes['sueldos_estimados'] ? ' (incluye ' . $this->m($mes['sueldos_estimados']) . ' estimado de días aún no cerrados)' : '')
            . ' · Masajes ' . $this->m($mes['masajes']) . ' · Insumos ' . $this->m($mes['insumos']);
        $l[] = 'Resultado ' . $this->m($mes['resultado']) . ' → margen ' . ($mes['margen'] === null ? '-' : $mes['margen'] . '%');
        $l[] = 'IVA de las ventas del mes (a separar): ' . $this->m($mes['iva']);
        $l[] = '';
        $l[] = 'RESERVAS';
        foreach ($p['detalle'] as $r) {
            $l[] = '#' . $r['id'] . ' ' . $r['cliente'] . ' · ' . $r['personas'] . ' p · ' . $r['programa'] . ($r['estado'] && $r['estado'] !== 'confirmada' ? ' · ' . $r['estado'] : '');
            if ($r['masajes']) {
                $l[] = '   Masajes: ' . implode(', ', $r['masajes']);
            }
            if ($r['obs']) {
                $l[] = '   Obs: ' . $r['obs'];
            }
        }
        return implode("\n", $l);
    }

    private function enviarWhatsapp(string $texto): void
    {
        $url = trim((string) env('HA_ALERTA_WEBHOOK', ''));
        if ($url === '') {
            Log::warning('[ReporteManana] Falta HA_ALERTA_WEBHOOK');
            return;
        }
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode(['mensaje' => $texto], JSON_UNESCAPED_UNICODE),
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable $e) {
            Log::warning('[ReporteManana] WhatsApp: ' . $e->getMessage());
        }
    }

    private function enviarCorreo(array $p, string $texto): void
    {
        $destinos = array_filter(array_map('trim', explode(',', (string) env('TURNOS_CORREO', ''))));
        if (! $destinos) {
            $c = \App\BotSeccionTexto::where('clave', 'alerta_correo')->value('contenido');
            if ($c) {
                $destinos = [$c];
            }
        }
        if (! $destinos) {
            return;
        }
        try {
            Mail::raw($texto, function ($m) use ($destinos, $p) {
                $m->to($destinos)->subject('Botacura mañana ' . $p['etiqueta'] . ': ' . $p['personas'] . ' personas · margen ' . ($p['margen'] ?? '-') . '%');
            });
        } catch (\Throwable $e) {
            Log::warning('[ReporteManana] Correo: ' . $e->getMessage());
        }
    }
}
