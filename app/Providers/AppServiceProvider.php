<?php

namespace App\Providers;

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
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Inventario: cada venta descuenta insumos según receta
        \App\DetalleConsumo::observe(\App\Observers\DetalleConsumoObserver::class);
        \App\DetalleVentaDirecta::observe(\App\Observers\DetalleVentaDirectaObserver::class);
    }
}
