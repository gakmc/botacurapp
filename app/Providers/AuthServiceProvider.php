<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',

        'App\Role' => 'App\Policies\RolePolicy',
        'App\Permission' => 'App\Policies\PermissionPolicy',
        'App\User'=>'App\Policies\UserPolicy',
        'App\Programa' =>'App\Policies\ProgramaPolicy',
        'App\Servicio' =>'App\Policies\ServicioPolicy',
        'App\Cliente' =>'App\Policies\ClientePolicy',
        'App\Reserva' =>'App\Policies\ReservaPolicy',
        'App\Sueldo' =>'App\Policies\SueldoPolicy',
        'App\Ticket' =>'App\Policies\TicketPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Auditoría: el acceso no depende del rol (ser administrador no basta).
        Gate::define('ver-auditoria', function ($user) {
            return $user->puede_ver_auditoria();
        });

        Gate::define('gestionar-auditoria', function ($user) {
            return $user->es_gestor_auditoria();
        });

        // Tickets: ver todos los departamentos (checkbox "Solo los de mi rol" en la bandeja)
        Gate::define('supervisar-tickets', function ($user) {
            return $user->supervisaTickets();
        });
    }
}
