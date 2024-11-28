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
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
