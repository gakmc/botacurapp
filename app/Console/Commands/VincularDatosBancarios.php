<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;

/**
 * Carga única: vincula RUT, boletea, datos bancarios y correo personal
 * a los 18 trabajadores confirmados en chat (linkeo manual por user_id,
 * verificado uno por uno antes de escribir nada).
 *
 * Uso:
 *   php artisan sueldos:vincular-datos-bancarios --dry-run   (solo muestra cambios)
 *   php artisan sueldos:vincular-datos-bancarios              (aplica los cambios)
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class VincularDatosBancarios extends Command
{
    protected $signature = 'sueldos:vincular-datos-bancarios {--dry-run}';

    protected $description = 'Vincula rut/boletea/banco/cuenta/correo_personal a los 18 usuarios confirmados';

    private $datos = [
        29 => ['rut' => '17924883-2', 'boletea' => false, 'banco' => 'Itaú Corpbanca',   'tipo_cuenta' => 'Cuenta Corriente', 'numero_cuenta' => '224936428',    'correo_personal' => 'ad.cadiz@hotmail.com'],
        8  => ['rut' => '17543174-8', 'boletea' => false, 'banco' => 'BCI',              'tipo_cuenta' => 'Cuenta Corriente', 'numero_cuenta' => '50590642',     'correo_personal' => 'cwirlok@gmail.com'],
        38 => ['rut' => '22498495-2', 'boletea' => false, 'banco' => 'BancoEstado',      'tipo_cuenta' => 'Cuenta RUT',       'numero_cuenta' => '22498495',     'correo_personal' => 'hola@botacura.cl'],
        20 => ['rut' => '13465824-K', 'boletea' => false, 'banco' => 'BancoEstado',      'tipo_cuenta' => 'Cuenta RUT',       'numero_cuenta' => '13465824',     'correo_personal' => 'hola@botacura.cl'],
        16 => ['rut' => '21522638-7', 'boletea' => false, 'banco' => 'Banco de Chile',   'tipo_cuenta' => 'Cuenta Vista',     'numero_cuenta' => '2580824204',   'correo_personal' => 'hola@botacura.cl'],
        23 => ['rut' => '15701595-8', 'boletea' => false, 'banco' => 'BancoEstado',      'tipo_cuenta' => 'Cuenta RUT',       'numero_cuenta' => '15701595',     'correo_personal' => null],
        13 => ['rut' => '21447378-K', 'boletea' => true,  'banco' => 'Banco Falabella',  'tipo_cuenta' => 'Cuenta Corriente', 'numero_cuenta' => '19822068078',  'correo_personal' => 'fernandocastro20112003@gmail.com'],
        58 => ['rut' => '20224003-8', 'boletea' => false, 'banco' => 'BancoEstado',      'tipo_cuenta' => 'Cuenta RUT',       'numero_cuenta' => '20224003',     'correo_personal' => 'hola@botacura.cl'],
        55 => ['rut' => '20559092-7', 'boletea' => false, 'banco' => 'BancoEstado',      'tipo_cuenta' => 'Cuenta RUT',       'numero_cuenta' => '20559092',     'correo_personal' => 'fcabahamondes@gmail.com'],
        3  => ['rut' => '18623689-0', 'boletea' => false, 'banco' => 'Banco Falabella',  'tipo_cuenta' => 'Cuenta Corriente', 'numero_cuenta' => '10070355690',  'correo_personal' => 'gak335@gmail.com'],
        15 => ['rut' => '21675782-3', 'boletea' => false, 'banco' => 'Banco Falabella',  'tipo_cuenta' => 'Cuenta Corriente', 'numero_cuenta' => '15140165593',  'correo_personal' => 'hola@botacura.cl'],
        18 => ['rut' => '21401993-0', 'boletea' => false, 'banco' => 'Banco de Chile',   'tipo_cuenta' => 'Cuenta Vista',     'numero_cuenta' => '341925132',    'correo_personal' => 'javiera.castro203@gmail.com'],
        24 => ['rut' => '10854490-2', 'boletea' => false, 'banco' => 'Banco de Chile',   'tipo_cuenta' => 'Cuenta Vista',     'numero_cuenta' => '336225314',    'correo_personal' => 'juanitomondaca1@gmail.com'],
        27 => ['rut' => '19317497-3', 'boletea' => false, 'banco' => 'BancoEstado',      'tipo_cuenta' => 'Cuenta RUT',       'numero_cuenta' => '19317497',     'correo_personal' => 'hola@botacura.cl'],
        11 => ['rut' => '15824773-9', 'boletea' => false, 'banco' => 'BancoEstado',      'tipo_cuenta' => 'Cuenta RUT',       'numero_cuenta' => '15824773',     'correo_personal' => 'hola@botacura.cl'],
        14 => ['rut' => '21073497-K', 'boletea' => true,  'banco' => 'BancoEstado',      'tipo_cuenta' => 'Cuenta RUT',       'numero_cuenta' => '21073497',     'correo_personal' => 'lovito.espinoza@gmail.com'],
        2  => ['rut' => '21063175-5', 'boletea' => true,  'banco' => 'Banco Santander',  'tipo_cuenta' => 'Cuenta Corriente', 'numero_cuenta' => '91165015',     'correo_personal' => 'pamelatapiar@gmail.com'],
        7  => ['rut' => '20206384-5', 'boletea' => true,  'banco' => 'Banco Santander',  'tipo_cuenta' => 'Cuenta Vista',     'numero_cuenta' => '1713615591',   'correo_personal' => 'hola@botacura.cl'],
    ];

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? 'MODO DRY-RUN: no se escribe nada, solo se muestra.' : 'APLICANDO CAMBIOS...');
        $this->line('');

        foreach ($this->datos as $id => $d) {
            $user = User::find($id);
            if (!$user) {
                $this->error("  #$id NO EXISTE — se omite");
                continue;
            }

            $this->line("#{$id} {$user->name}");
            $this->line("    rut:             '{$user->rut}' -> '{$d['rut']}'");
            $this->line("    boletea:         " . ($user->boletea ? 'true' : 'false') . ' -> ' . ($d['boletea'] ? 'true' : 'false'));
            $this->line("    banco:           '{$user->banco}' -> '{$d['banco']}'");
            $this->line("    tipo_cuenta:     '{$user->tipo_cuenta_bancaria}' -> '{$d['tipo_cuenta']}'");
            $this->line("    numero_cuenta:   '{$user->numero_cuenta_bancaria}' -> '{$d['numero_cuenta']}'");
            $this->line("    correo_personal: '{$user->correo_personal}' -> '" . ($d['correo_personal'] ?? '(vacío)') . "'");
            $this->line('');

            if (!$dryRun) {
                $user->rut = $d['rut'];
                $user->boletea = $d['boletea'];
                $user->banco = $d['banco'];
                $user->tipo_cuenta_bancaria = $d['tipo_cuenta'];
                $user->numero_cuenta_bancaria = $d['numero_cuenta'];
                $user->correo_personal = $d['correo_personal'];
                $user->save();
            }
        }

        $this->info($dryRun ? 'Dry-run completo. Revisa arriba y corre sin --dry-run para aplicar.' : 'Cambios aplicados a los ' . count($this->datos) . ' usuarios.');
    }
}
