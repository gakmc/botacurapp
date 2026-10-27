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
            [$email1, $email2] = $this->correosAviso($user->correo_personal);

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
                $email2,
                '',
            ]);
        }

        return [
            'csv'      => implode("\n", $filas),
            'omitidos' => $omitidos,
        ];
    }

    /**
     * Email 1 = correo del trabajador; Email 2 = correo de control de Botacura
     * (config bancos.email_control), para que el aviso de cada transferencia
     * llegue a esa casilla y Remuneraciones marque la semana como pagada.
     * Si el trabajador no tiene correo, el de control queda en Email 1.
     * Si ambos son el mismo, va una sola vez.
     *
     * @return array [email1, email2]
     */
    private function correosAviso(?string $correoTrabajador): array
    {
        $trabajador = trim((string) $correoTrabajador);
        $control    = trim((string) config('bancos.email_control', 'botacura.info@gmail.com'));

        if ($trabajador === '') {
            return [$control, ''];
        }

        if ($control === '' || strcasecmp($trabajador, $control) === 0) {
            return [$trabajador, ''];
        }

        return [$trabajador, $control];
    }

    /**
     * El banco agrega el guión del RUT por su cuenta, así que el CSV va
     * sin guión: solo dígitos + DV pegados, sin puntos ni espacios.
     * Ej: "13465824--K" o "13.465.824-6" -> "134658246".
     */
    private function normalizarRut(?string $rut): string
    {
        if (empty($rut)) {
            return '';
        }

        return strtoupper(str_replace(['.', ' ', '-'], '', $rut));
    }
}
