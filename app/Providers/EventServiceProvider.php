<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    const COOKIE_SESION = 'botacura_sesion_previa';

    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        \App\Listeners\AuditoriaAutenticacion::class,
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // Cookie marcadora: permite distinguir una sesión expirada de un visitante sin sesión
        Event::listen(Login::class, function () {
            Cookie::queue(self::COOKIE_SESION, '1', 60 * 24 * 30);
        });

        Event::listen(Logout::class, function () {
            Cookie::queue(Cookie::forget(self::COOKIE_SESION));
        });
    }
}
