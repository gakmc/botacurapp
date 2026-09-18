<?php

namespace App\Console\Commands;

use App\HonorarioBte;
use App\Services\CsvPagoBancoService;
use App\Sueldo;
use App\SueldoPagado;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Reproduce exactamente la lógica de SueldoController::exportarCsvSemana()
 * para una semana puntual, pero mostrando en pantalla por qué cada usuario
 * queda incluido u omitido del CSV (en vez de solo un error genérico).
 *
 * Uso: php artisan sueldos:diagnosticar-csv-semana 2026-08-10 2026-08-16
 */
class DiagnosticarCsvSemana extends Command
{
    protected $signature = 'sueldos:diagnosticar-csv-semana {inicio} {fin}';
    protected $description = 'Diagnostica por qué el CSV de una semana sale vacío';

    public function handle(CsvPagoBancoService $csvService)
    {
        $inicio = Carbon::parse($this->argument('inicio'))->startOfDay();
        $fin    = Carbon::parse($this->argument('fin'))->endOfDay();
        $anio   = $inicio->year;

        $this->info("Semana: {$inicio->toDateString()} a {$fin->toDateString()}");

        $sueldos = Sueldo::with('user')
            ->whereBetween('dia_trabajado', [$inicio->toDateString(), $fin->toDateString()])
            ->get();

        $this->info("Registros en 'sueldos' encontrados en el rango: " . $sueldos->count());

        if ($sueldos->isEmpty()) {
            $this->error("No hay filas en 'sueldos' para ese rango de fechas exacto. Revisa que inicio/fin coincidan con dia_trabajado.");
            return;
        }

        $honorariosPorRut = HonorarioBte::anio($anio)
            ->whereIn('rut_emisor', User::where('boletea', true)->whereNotNull('rut')->pluck('rut'))
            ->get()
            ->groupBy('rut_emisor');

        $usuarios = [];

        foreach ($sueldos as $sueldo) {
            if (! $sueldo->user) {
                continue;
            }

            $userId = $sueldo->user->id;
            $roles  = $sueldo->user->list_roles();
            $esMaso = is_array($roles) ? in_array('Masoterapeuta', $roles)
                : (stripos((string) $roles, 'Masoterapeuta') !== false);

            if (! isset($usuarios[$userId])) {
                $boletea = (bool) $sueldo->user->boletea;
                $bteRow  = null;

                if ($boletea && $sueldo->user->rut) {
                    $bteSemana = $honorariosPorRut->get($sueldo->user->rut, collect());
                    $bteRow    = $bteSemana->first(function ($h) use ($inicio, $fin) {
                        return $h->fecha_emision && $h->fecha_emision->between($inicio, $fin);
                    });
                }

                $usuarios[$userId] = [
                    'user'          => $sueldo->user,
                    'sueldos'       => 0,
                    'propinas'      => 0,
                    'bono'          => 0,
                    'boletea'       => $boletea,
                    'bte_bruto'     => $bteRow->monto_bruto ?? 0,
                    'bte_retencion' => $bteRow->monto_retenido ?? 0,
                    'bte_neto'      => $bteRow ? ($bteRow->monto_pagado ?: ($bteRow->monto_bruto - $bteRow->monto_retenido)) : 0,
                ];
            }

            $usuarios[$userId]['sueldos']  += $esMaso ? $sueldo->total_pagar : $sueldo->valor_dia;
            $usuarios[$userId]['propinas'] += $esMaso ? 0 : ($sueldo->sub_sueldo - $sueldo->valor_dia);
        }

        $pagos = SueldoPagado::where('semana_inicio', $inicio->toDateString())
            ->where('semana_fin', $fin->toDateString())
            ->get();

        foreach ($pagos as $pago) {
            if (isset($usuarios[$pago->user_id])) {
                $usuarios[$pago->user_id]['bono'] = (int) $pago->bono;
            }
        }

        $seleccionados = [];
        $this->info("\nUsuarios detectados en la semana:");
        foreach ($usuarios as $userId => $datos) {
            $netoBase = ($datos['boletea'] && $datos['bte_bruto'] > 0) ? $datos['bte_neto'] : $datos['sueldos'];
            $total = $netoBase + $datos['propinas'] + $datos['bono'];

            $u = $datos['user'];
            $faltantes = [];
            if (empty($u->rut)) $faltantes[] = 'RUT';
            if (empty($u->banco)) $faltantes[] = 'banco';
            if (empty($u->tipo_cuenta_bancaria)) $faltantes[] = 'tipo cuenta';
            if (empty($u->numero_cuenta_bancaria)) $faltantes[] = 'numero cuenta';

            $estado = empty($faltantes) ? 'OK datos bancarios' : ('FALTA: ' . implode(', ', $faltantes));

            $this->line(sprintf(
                "  - %s (id %d): total=\$%s | %s",
                $u->name,
                $userId,
                number_format($total, 0, '', '.'),
                $estado
            ));

            if ($total <= 0) {
                continue;
            }

            $seleccionados[] = [
                'user_id' => $userId,
                'total'   => $total,
                'inicio'  => $inicio->toDateString(),
                'fin'     => $fin->toDateString(),
            ];
        }

        if (empty($seleccionados)) {
            $this->error("\nNingún usuario quedó con total > 0. El CSV sale vacío por eso.");
            return;
        }

        $resultado = $csvService->generar($seleccionados);

        $this->info("\nFilas generadas en el CSV (sin contar encabezado): " . max(0, substr_count($resultado['csv'], "\n")));

        if (!empty($resultado['omitidos'])) {
            $this->warn("Omitidos del CSV:");
            foreach ($resultado['omitidos'] as $o) {
                $nombre = $o['user'] ? $o['user']->name : 'desconocido';
                $this->line("  - {$nombre}: {$o['motivo']}");
            }
        }

        if (empty(trim($resultado['csv'])) || substr_count($resultado['csv'], "\n") === 0) {
            $this->error("\nCONCLUSIÓN: el CSV sale vacío porque NINGÚN usuario de esta semana tiene los 4 datos bancarios completos (RUT, banco, tipo de cuenta, número de cuenta). Hay que cargarlos en /datos-bancarios.");
        } else {
            $this->info("\nCONCLUSIÓN: el CSV debería haberse generado con contenido. Si igual no baja nada en el navegador, es un tema del navegador/sesión, no del backend.");
        }
    }
}
