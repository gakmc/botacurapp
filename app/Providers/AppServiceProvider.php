<?php

namespace App\Providers;

use App\Auditoria;
use App\Services\AuditoriaService;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(\App\Services\AuditoriaService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Auditoría en la cola: cada job parte con contexto limpio y, por defecto,
        // queda a nombre de "Sistema (cola: NombreDelJob)". El job puede fijar un
        // actor más específico (ej. ProcesarOrdenWoocommerce → "Sistema (WooCommerce)").
        Queue::before(function (JobProcessing $evento) {
            app(AuditoriaService::class)
                ->reiniciarContexto()
                ->establecerActor(null, Auditoria::ACTOR_SISTEMA, 'cola: ' . class_basename($evento->job->resolveName()));
        });
    }
}
