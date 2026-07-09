<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FechasDisponiblesController;

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

// ─────────────────────────────────────────────────────────────────────────
// Rutas del Chatbot Bot-Acura (WhatsApp + Instagram)
// Protegidas con middleware bot.token (header X-Bot-Token)
//
// Endpoints disponibles:
//   GET  /api/bot/ping                        → verificar conectividad
//   GET  /api/bot/programas                   → listar programas activos
//   GET  /api/bot/disponibilidad?fecha=...    → consultar cupos por fecha
//   POST /api/bot/clientes/buscar-o-crear     → resolver cliente por WA/IG
//   POST /api/bot/reservas                    → crear reserva + venta
//   POST /api/bot/reservas/{id}/pago          → registrar abono recibido
//   GET  /api/bot/conversacion/{usuario_id}   → obtener estado conversación
//   POST /api/bot/conversacion                → crear/actualizar conversación
// ─────────────────────────────────────────────────────────────────────────
Route::prefix('bot')->namespace('Api')->middleware('bot.token')->group(function () {

    Route::get('ping',                          'BotController@ping')               ->name('bot.ping');
    Route::get('programas',                     'BotController@programas')          ->name('bot.programas');
    Route::get('disponibilidad',                'BotController@disponibilidad')     ->name('bot.disponibilidad');
    Route::post('clientes/buscar-o-crear',      'BotController@buscarOCrearCliente')->name('bot.clientes.buscar-o-crear');
    Route::post('reservas',                     'BotController@crearReserva')       ->name('bot.reservas.crear');
    Route::post('reservas/{id}/pago',           'BotController@registrarPago')      ->name('bot.reservas.pago');
    Route::get('conversacion/{usuario_id}',     'BotController@getConversacion')    ->name('bot.conversacion.get');
    Route::post('conversacion',                 'BotController@upsertConversacion') ->name('bot.conversacion.upsert');
    Route::get('productos',                     'BotController@productos')          ->name('bot.productos');
    Route::post('menu',                         'BotController@guardarMenu')        ->name('bot.menu');
});

Route::prefix('woocommerce')->namespace('Api')->group(function(){

    // Endpoint de prueba — no requiere auth, solo verifica que el servidor responde
    Route::get('ping', 'WoocommerceWebhookController@ping')->name('woocommerce.ping');

        // GET para verificación de WooCommerce al guardar el webhook
    Route::get('webhook', 'WoocommerceWebhookController@ping')
        ->name('woocommerce.webhook.verify');
    
    // Endpoint real del webhook (lo activaremos en el paso siguiente)
    Route::post('webhook', 'WoocommerceWebhookController@handle')
        ->name('woocommerce.webhook');
});

Route::middleware('auth.apikey')->group(function () {
    Route::get('/fechas-disponibles', [FechasDisponiblesController::class, 'index']);
});
Route::get('/iot/ping', 'Api\IotController@ping');
Route::get('/iot/proxima-tinaja', 'Api\IotController@proximaTinaja');
Route::get('/iot/tinajas/proxima-reserva', 'Api\TinajaController@proximaReserva');
