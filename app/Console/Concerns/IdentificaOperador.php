<?php

namespace App\Console\Concerns;

use App\Auditoria;
use App\Services\AuditoriaService;
use App\User;
use Illuminate\Support\Facades\Hash;

/**
 * Identifica a la persona que ejecuta un comando manualmente, pidiendo las
 * credenciales de su usuario en la plataforma. Si son válidas, queda fijada
 * como actor de la auditoría para el resto del proceso.
 *
 * El comando que lo use debe declarar la opción {--usuario= : Email de quien ejecuta}.
 */
trait IdentificaOperador
{
    /**
     * @return User|null null si las credenciales no son válidas
     */
    protected function identificarOperador()
    {
        $servicio = app(AuditoriaService::class);
        $detalle  = $this->getName() . ', usuario SO: ' . $servicio->usuarioSistemaOperativo();

        $this->line('Este comando queda registrado en la auditoría. Identifícate con tu usuario de la plataforma.');

        $email    = ($this->hasOption('usuario') ? $this->option('usuario') : null) ?: $this->ask('Email');
        $password = $this->secret('Contraseña');

        $user = $email ? User::where('email', $email)->first() : null;

        if (! $user || ! $user->activo || ! $password || ! Hash::check($password, $user->password)) {
            $servicio->establecerActor(null, Auditoria::ACTOR_CONSOLA, $detalle);
            $servicio->registrar([
                'categoria'   => Auditoria::CATEGORIA_SEGURIDAD,
                'evento'      => Auditoria::EVENTO_LOGIN_FALLIDO,
                'descripcion' => 'falló la identificación por consola con el email "' . $email . '" al ejecutar ' . $this->getName(),
            ]);

            $this->error('Credenciales inválidas o usuario inactivo. El comando no se ejecutó.');

            return null;
        }

        $servicio->establecerActor($user, Auditoria::ACTOR_CONSOLA, $detalle);

        return $user;
    }
}
