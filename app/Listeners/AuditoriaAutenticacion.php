<?php

namespace App\Listeners;

use App\Auditoria;
use App\Services\AuditoriaService;
use App\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;

/**
 * Registra en la auditoría (categoría seguridad) los eventos de autenticación web.
 * La API JWT (tymon) no dispara estos eventos: se audita en Api\AuthController.
 */
class AuditoriaAutenticacion
{
    /** @var AuditoriaService */
    protected $auditoria;

    public function __construct(AuditoriaService $auditoria)
    {
        $this->auditoria = $auditoria;
    }

    public function login(Login $event)
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->auditoria->registrarComo($event->user, [
            'categoria'   => Auditoria::CATEGORIA_SEGURIDAD,
            'evento'      => Auditoria::EVENTO_LOGIN,
            'auditable'   => $event->user,
            'contexto'    => $event->user,
            'descripcion' => 'inició sesión' . ($event->remember ? ' (con "recordarme")' : ''),
        ]);
    }

    public function logout(Logout $event)
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->auditoria->registrarComo($event->user, [
            'categoria'   => Auditoria::CATEGORIA_SEGURIDAD,
            'evento'      => Auditoria::EVENTO_LOGOUT,
            'auditable'   => $event->user,
            'contexto'    => $event->user,
            'descripcion' => 'cerró sesión',
        ]);
    }

    public function fallido(Failed $event)
    {
        $email = isset($event->credentials['email']) ? $event->credentials['email'] : '(sin email)';
        $user  = $event->user instanceof User ? $event->user : null;

        $this->auditoria->registrarComo(null, [
            'categoria'   => Auditoria::CATEGORIA_SEGURIDAD,
            'evento'      => Auditoria::EVENTO_LOGIN_FALLIDO,
            'auditable'   => $user,
            'contexto'    => $user,
            // user_id permite filtrar por la cuenta atacada; el actor sigue siendo "Visitante no identificado"
            'user_id'     => $user ? $user->id : null,
            'descripcion' => 'intentó iniciar sesión con el email "' . $email . '"'
                . ($user ? ' (contraseña incorrecta)' : ' (email no registrado)'),
        ], Auditoria::ACTOR_INVITADO);
    }

    public function bloqueo(Lockout $event)
    {
        $email = $event->request->input('email', '(sin email)');

        $this->auditoria->registrarComo(null, [
            'categoria'   => Auditoria::CATEGORIA_SEGURIDAD,
            'evento'      => Auditoria::EVENTO_LOGIN_FALLIDO,
            'descripcion' => 'fue bloqueado temporalmente por demasiados intentos fallidos con el email "' . $email . '"',
        ], Auditoria::ACTOR_INVITADO);
    }

    public function contrasenaRestablecida(PasswordReset $event)
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->auditoria->registrarComo($event->user, [
            'categoria'   => Auditoria::CATEGORIA_SEGURIDAD,
            'evento'      => Auditoria::EVENTO_EDITO,
            'auditable'   => $event->user,
            'contexto'    => $event->user,
            'descripcion' => 'restableció su contraseña mediante el enlace enviado por correo',
        ]);
    }

    public function subscribe($events)
    {
        $events->listen(Login::class, static::class . '@login');
        $events->listen(Logout::class, static::class . '@logout');
        $events->listen(Failed::class, static::class . '@fallido');
        $events->listen(Lockout::class, static::class . '@bloqueo');
        $events->listen(PasswordReset::class, static::class . '@contrasenaRestablecida');
    }
}
