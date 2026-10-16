<?php

namespace App\Console\Commands;

use App\AuditoriaAutorizado;
use Illuminate\Console\Command;

class AuditoriaAutorizados extends Command
{
    protected $signature = 'auditoria:autorizados';

    protected $description = 'Lista los usuarios con acceso a la auditoría (gestores y lectores)';

    public function handle()
    {
        $autorizados = AuditoriaAutorizado::with(['user', 'otorgante'])
            ->orderBy('nivel')
            ->orderBy('otorgado_at')
            ->get();

        if ($autorizados->isEmpty()) {
            $this->info('Nadie tiene acceso a la auditoría. Usa: php artisan auditoria:autorizar {email}');
            return 0;
        }

        $this->table(
            ['Usuario', 'Email', 'Nivel', 'Activo', 'Otorgado por', 'Fecha'],
            $autorizados->map(function ($a) {
                return [
                    optional($a->user)->name,
                    optional($a->user)->email,
                    ucfirst($a->nivel),
                    optional($a->user)->activo ? 'Sí' : 'No',
                    optional($a->otorgante)->name ?: '-',
                    $a->otorgado_at ? $a->otorgado_at->format('d-m-Y H:i') : '-',
                ];
            })->all()
        );

        return 0;
    }
}
