<?php

namespace App\Services;

use App\User;
use Carbon\Carbon;

/**
 * Arma el CSV de "Transferencias a Terceros" de BancoEstado Empresas
 * a partir de la selección de sueldos hecha en la vista de Remuneraciones.
 *
 * Formato (13 columnas, separadas por ";"), según ARCHIVOEJEMPLO.csv y
 * las tablas oficiales del banco (config/bancos.php):
 *
 *   Tipo de Cuenta Origen; Cuenta Origen; Codigo Banco Destino;
 *   Tipo de Cuenta Destino; Cuenta Destino; Rut Beneficiario;
 *   Nombre Beneficiario; Monto Transferencia; Concepto;
 *   Mensaje a Beneficiario; Email 1; Email 2; Email 3
 */
class CsvPagoBancoService
{
    /**
     * @param  array $seleccionados  cada item: ['user_id'=>, 'total'=>, 'inicio'=>, 'fin'=>]
     * @return array ['csv' => string, 'omitidos' => array de ['user'=>User, 'motivo'=>string]]
     */
    public function generar(array $seleccionados)
    {
        $cuentaOrigen = config('bancos.cuenta_origen');
        $nombresBanco = config('bancos.bancos');
        $tiposCuenta  = array_keys(config('bancos.tipos_cuenta_destino'));

        $filas    = [];
        $omitidos = [];

        // Encabezado, igual al ARCHIVOEJEMPLO.csv provisto por el banco
        $filas[] = 'Tipo de Cuenta Origen; Cuenta Origen; Codigo Banco Destino; Tipo de Cuenta Destino; Cuenta Destino; Rut Beneficiario; Nombre Beneficiario; Monto Transferencia; Concepto; Mensaje a Beneficiario; Email 1;Email 2;Email 3';

        foreach ($seleccionados as $item) {
            $user = User::find($item['user_id']);

            if (!$user) {
                $omitidos[] = ['user' => null, 'motivo' => "user_id {$item['user_id']} no existe"];
                continue;
            }

            $faltantes = [];
            if (empty($user->rut)) {
                $faltantes[] = 'RUT';
            }
            if (empty($user->banco) || !isset($nombresBanco[$user->banco])) {
                $faltantes[] = 'banco';
            }
            if (empty($user->tipo_cuenta_bancaria) || !in_array($user->tipo_cuenta_bancaria, $tiposCuenta)) {
                $faltantes[] = 'tipo de cuenta';
            }
            if (empty($user->numero_cuenta_bancaria)) {
                $faltantes[] = 'número de cuenta';
            }

            if (!empty($faltantes)) {
                $omitidos[] = ['user' => $user, 'motivo' => 'Falta: ' . implode(', ', $faltantes)];
                continue;
            }

            $inicio  = Carbon::parse($item['inicio'])->locale('es')->isoFormat('DD/MM');
            $fin     = Carbon::parse($item['fin'])->locale('es')->isoFormat('DD/MM');
            $monto   = (int) round((float) $item['total']);
            // Máximo 20 caracteres permitidos por el banco en "Concepto".
            // "Sueldo " (7) + "DD/MM" (5) + "-" (1) + "DD/MM" (5) = 18, siempre entra.
            $concepto = "Sueldo {$inicio}-{$fin}";
            $mensaje  = 'Pago de remuneracion Botacura';
            $email1   = $user->correo_personal ?: '';

            $filas[] = implode(';', [
                $cuentaOrigen['tipo'],
                $cuentaOrigen['numero'],
                $user->banco,
                $user->tipo_cuenta_bancaria,
                $user->numero_cuenta_bancaria,
                $this->normalizarRut($user->rut),
                $user->name,
                $monto,
                $concepto,
                $mensaje,
                $email1,
                '',
                '',
            ]);
        }

        return [
            'csv'      => implode("\n", $filas),
            'omitidos' => $omitidos,
        ];
    }

    /**
     * Normaliza el RUT a "cuerpo-DV" con un único guión, sin importar cómo
     * haya quedado guardado en la base (con doble guión, sin guión, con
     * puntos, etc). Ej: "13465824--K" o "134658246" -> "13465824-6".
     */
    private function normalizarRut(?string $rut): string
    {
        if (empty($rut)) {
            return '';
        }

        $limpio = strtoupper(str_replace(['.', ' ', '-'], '', $rut));

        if (strlen($limpio) < 2) {
            return $limpio;
        }

        $dv     = substr($limpio, -1);
        $cuerpo = substr($limpio, 0, -1);

        return "{$cuerpo}-{$dv}";
    }
}
