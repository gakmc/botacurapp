<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;

/**
 * Carga única: convierte los valores en texto libre que quedaron guardados
 * en users.banco / users.tipo_cuenta_bancaria (antes de tener el listado
 * oficial del banco) a los códigos de config/bancos.php.
 *
 * Uso: php artisan sueldos:normalizar-codigos-banco --dry-run
 */
class NormalizarCodigosBancoUsuarios extends Command
{
    protected $signature = 'sueldos:normalizar-codigos-banco {--dry-run}';

    protected $description = 'Convierte banco/tipo_cuenta_bancaria de texto libre a los códigos oficiales del banco';

    // Mapeo confirmado a mano contra Codigos_Banco.xlsx / Codigos_Cuenta_Destino.xlsx
    private $mapaBanco = [
        'Banco Santander'  => '037',
        'Banco Falabella'  => '051',
        'BCI'              => '016',
        'BancoEstado'      => '012',
        'Banco de Chile'   => '001',
        'Itaú Corpbanca'   => '039', // confirmado por el usuario: BANCO ITAU
    ];

    private $mapaTipoCuenta = [
        'Cuenta Corriente' => 'CCT',
        'Cuenta Vista'      => 'CTV',
        'Cuenta RUT'        => 'CRUT',
        'Cuenta de Ahorro'  => 'AHO',
    ];

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? 'MODO DRY-RUN: no se escribe nada.' : 'APLICANDO...');
        $this->line('');

        $codigosBancoValidos = array_keys(config('bancos.bancos'));
        $codigosTipoValidos  = array_keys(config('bancos.tipos_cuenta_destino'));

        $usuarios = User::where(function ($q) {
            $q->whereNotNull('banco')->orWhereNotNull('tipo_cuenta_bancaria');
        })->get();

        foreach ($usuarios as $u) {
            $bancoActual = $u->banco;
            $tipoActual  = $u->tipo_cuenta_bancaria;

            $nuevoBanco = $bancoActual;
            if ($bancoActual && !in_array($bancoActual, $codigosBancoValidos)) {
                if (isset($this->mapaBanco[$bancoActual])) {
                    $nuevoBanco = $this->mapaBanco[$bancoActual];
                } else {
                    $this->error("#{$u->id} {$u->name}: banco '{$bancoActual}' sin mapeo conocido — revisar a mano.");
                    continue;
                }
            }

            $nuevoTipo = $tipoActual;
            if ($tipoActual && !in_array($tipoActual, $codigosTipoValidos)) {
                if (isset($this->mapaTipoCuenta[$tipoActual])) {
                    $nuevoTipo = $this->mapaTipoCuenta[$tipoActual];
                } else {
                    $this->error("#{$u->id} {$u->name}: tipo_cuenta '{$tipoActual}' sin mapeo conocido — revisar a mano.");
                    continue;
                }
            }

            if ($nuevoBanco === $bancoActual && $nuevoTipo === $tipoActual) {
                continue; // ya estaba en código, nada que hacer
            }

            $this->line("#{$u->id} {$u->name}: banco '{$bancoActual}' -> '{$nuevoBanco}' | tipo_cuenta '{$tipoActual}' -> '{$nuevoTipo}'");

            if (!$dryRun) {
                $u->banco = $nuevoBanco;
                $u->tipo_cuenta_bancaria = $nuevoTipo;
                $u->save();
            }
        }

        $this->line('');
        $this->info($dryRun ? 'Dry-run completo.' : 'Normalización aplicada.');
    }
}
