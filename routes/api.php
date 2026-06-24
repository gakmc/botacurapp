<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', 'Api\UserController@show')->name('user');

Route::post('register', 'Api\AuthController@register');
Route::post('login', 'Api\AuthController@login');

Route::middleware('auth:api')->group(function () {
    Route::get('me', 'Api\AuthController@me');
    Route::get('sueldos', 'Api\SueldoController@index');
    Route::post('logout', 'Api\AuthController@logout');
    Route::post('refresh', 'Api\AuthController@refresh');

});

// -------------------------------------------------------------------------
// IoT — Gas (Home Assistant)
// POST /api/iot/gas/registrar
//   tipo_operacion: pago_proveedor | instalacion_cilindro
// No requiere auth: se recomienda validar por token de HA en el controlador
// -------------------------------------------------------------------------
Route::prefix('iot')->namespace('Api')->group(function () {
    Route::post('gas/registrar', 'GasIotController@registrar')->name('iot.gas.registrar');
});

Route::prefix('woocommerce')->namespace('Api')->group(function(){

    // Endpoint de prueba — no requiere auth, solo verifica que el servidor responde
    Route::get('ping', 'WoocommerceWebhookController@ping')->name('woocommerce.ping');
    
    // Endpoint real del webhook (lo activaremos en el paso siguiente)
    Route::post('webhook', 'WoocommerceWebhookController@handle')
        ->name('woocommerce.webhook');
});

