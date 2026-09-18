<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpirarReservaHolds extends Command
{
    protected $signature = 'reservaholds:expirar';
    protected $description = 'Marca como expirado cualquier hold de reserva (transferencia pendiente de comprobante) cuyo tiempo ya vencio.';

    public function handle()
    {
        $n = DB::table('reserva_holds')
            ->where('estado', 'activo')
            ->where('expira_en', '<=', now())
            ->update(['estado' => 'expirado', 'updated_at' => now()]);

        if ($n > 0) {
            $this->info("Holds expirados: {$n}");
        }

        return 0;
    }
}
