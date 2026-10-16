<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\AuditoriaService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * El cambio de contraseña se audita con el evento PasswordReset (a nombre del
     * propio usuario); se evita el registro genérico "editó el usuario" que
     * quedaría como hecho por un visitante no identificado.
     */
    protected function resetPassword($user, $password)
    {
        $auditoria = app(AuditoriaService::class);

        $auditoria->sinRegistro(function () use ($user, $password) {
            $this->setUserPassword($user, $password);
            $user->setRememberToken(Str::random(60));
            $user->save();
        });

        event(new PasswordReset($user));

        $this->guard()->login($user);
    }
}
