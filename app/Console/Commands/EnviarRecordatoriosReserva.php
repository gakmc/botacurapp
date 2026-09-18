<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnviarRecordatoriosReserva extends Command
{
    protected $signature = 'reservas:enviar-recordatorios {--dry-run}';

    protected $description = 'Envía recordatorio de WhatsApp 24h antes a las reservas de mañana (excluye canceladas)';

    public function handle(WhatsAppService $whatsapp)
    {
        $dryRun  = (bool) $this->option('dry-run');
        $manana  = Carbon::tomorrow()->toDateString();

        $reservas = DB::table('reservas as r')
            ->join('clientes as c', 'r.cliente_id', '=', 'c.id')
            ->leftJoin('programas as p', 'r.id_programa', '=', 'p.id')
            ->where('r.fecha_visita', $manana)
            ->where(function ($q) {
                $q->whereNull('r.estado')->orWhere('r.estado', '<>', 'cancelada');
            })
            ->whereNull('r.recordatorio_enviado_at')
            ->whereNotNull('c.whatsapp_cliente')
            ->select('r.id', 'r.cantidad_personas', 'c.nombre_cliente', 'c.whatsapp_cliente', 'p.nombre_programa')
            ->get();

        $this->info("Reservas para {$manana}: {$reservas->count()} pendientes de recordatorio.");

        $enviados = 0;
        $fallidos = 0;

        foreach ($reservas as $reserva) {
            $nombre     = $reserva->nombre_cliente ?: 'Cliente';
            $nombreProg = $reserva->nombre_programa ?: 'tu programa';

            $mensaje = "¡Hola {$nombre}! 👋 Te recordamos tu reserva en Botacura para *mañana*.\n\n"
                . "🌿 {$nombreProg}\n"
                . "👥 {$reserva->cantidad_personas} persona(s)\n\n"
                . "Nos pondremos en contacto contigo para coordinar el horario. ¡Te esperamos! 🏔️";

            if ($dryRun) {
                $this->line("[dry-run] Reserva #{$reserva->id} -> {$reserva->whatsapp_cliente}: {$nombre}");
                continue;
            }

            $ok = $whatsapp->enviarMensaje($reserva->whatsapp_cliente, $mensaje);

            if ($ok) {
                DB::table('reservas')->where('id', $reserva->id)->update([
                    'recordatorio_enviado_at' => now(),
                ]);
                $enviados++;
                Log::info("[Recordatorios] Enviado para reserva #{$reserva->id}");
            } else {
                $fallidos++;
                Log::error("[Recordatorios] Falló envío para reserva #{$reserva->id}");
            }
        }

        if (!$dryRun) {
            $this->info("Enviados: {$enviados} | Fallidos: {$fallidos}");
        }

        return 0;
    }
}
