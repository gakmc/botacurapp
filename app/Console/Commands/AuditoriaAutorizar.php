<?php

namespace App\Console\Commands;

use App\Auditoria;
use App\AuditoriaAutorizado;
use App\Console\Concerns\IdentificaOperador;
use App\Services\AuditoriaService;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Otorga o revoca el acceso de GESTOR a la auditoría.
 * Los gestores ven la auditoría y desde la plataforma pueden otorgar acceso (lector o gestor)
 * y revocar el de otras personas (nunca el propio). La consola no tiene esa restricción.
 */
class AuditoriaAutorizar extends Command
{
    use IdentificaOperador;

    protected $signature = 'auditoria:autorizar
                            {email : Email del usuario a autorizar}
                            {--revocar : Quita el acceso a la auditoría (gestor o lector)}
                            {--usuario= : Email de quien ejecuta el comando (se pedirá la contraseña)}';

    protected $description = 'Otorga (o revoca con --revocar) el acceso de gestor a la auditoría';

    public function handle(AuditoriaService $auditoria)
    {
        $objetivo = User::where('email', $this->argument('email'))->first();

        if (! $objetivo) {
            $this->error('No existe un usuario con el email ' . $this->argument('email') . '.');
            return 1;
        }

        $operador = $this->identificarOperador();
        if (! $operador) {
            return 1;
        }

        $actual = $objetivo->auditoriaAutorizacion;

        return $this->option('revocar')
            ? $this->revocar($auditoria, $objetivo, $actual)
            : $this->otorgar($auditoria, $objetivo, $actual, $operador);
    }

    protected function otorgar(AuditoriaService $auditoria, User $objetivo, $actual, User $operador)
    {
        if (! $objetivo->activo) {
            $this->error($objetivo->name . ' está inactivo; no se le puede otorgar acceso.');
            return 1;
        }

        if ($actual && $actual->es_gestor()) {
            $this->info($objetivo->name . ' ya es gestor de la auditoría.');
            return 0;
        }

        $pregunta = $actual
            ? '¿Subir a ' . $objetivo->name . ' de lector a gestor de la auditoría?'
            : '¿Otorgar a ' . $objetivo->name . ' acceso de gestor a la auditoría?';

        if (! $this->confirm($pregunta)) {
            $this->line('Operación cancelada.');
            return 0;
        }

        $nivelAnterior = $actual ? $actual->nivel : null;

        AuditoriaAutorizado::updateOrCreate(
            ['user_id' => $objetivo->id],
            [
                'nivel'        => AuditoriaAutorizado::NIVEL_GESTOR,
                'otorgado_por' => $operador->id,
                'otorgado_at'  => Carbon::now(),
            ]
        );

        $auditoria->registrar([
            'categoria'   => Auditoria::CATEGORIA_SEGURIDAD,
            'evento'      => Auditoria::EVENTO_ASIGNO,
            'auditable'   => $objetivo,
            'descripcion' => $nivelAnterior
                ? 'cambió por consola el acceso a la auditoría de ' . $objetivo->name . ' de lector a gestor'
                : 'otorgó por consola acceso de gestor a la auditoría a ' . $objetivo->name,
            'cambios'     => ['Acceso a auditoría' => [$nivelAnterior ? ucfirst($nivelAnterior) : 'Sin acceso', 'Gestor']],
        ]);

        $this->info($objetivo->name . ' ahora es gestor de la auditoría.');

        return 0;
    }

    protected function revocar(AuditoriaService $auditoria, User $objetivo, $actual)
    {
        if (! $actual) {
            $this->info($objetivo->name . ' no tiene acceso a la auditoría.');
            return 0;
        }

        if (! $this->confirm('¿Quitar a ' . $objetivo->name . ' el acceso de ' . $actual->nivel . ' a la auditoría?')) {
            $this->line('Operación cancelada.');
            return 0;
        }

        $nivel = $actual->nivel;
        $actual->delete();

        $auditoria->registrar([
            'categoria'   => Auditoria::CATEGORIA_SEGURIDAD,
            'evento'      => Auditoria::EVENTO_QUITO,
            'auditable'   => $objetivo,
            'descripcion' => 'revocó por consola el acceso de ' . $nivel . ' a la auditoría de ' . $objetivo->name,
            'cambios'     => ['Acceso a auditoría' => [ucfirst($nivel), 'Sin acceso']],
        ]);

        $this->info('Se quitó el acceso a la auditoría a ' . $objetivo->name . '.');

        return 0;
    }
}
