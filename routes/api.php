<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FechasDisponiblesController;
use App\Http\Controllers\Api\VerificarDisponibilidadController;

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


// -------------------------------------------------------------------------
// IoT — Gas (Home Assistant)
// POST /api/iot/gas/registrar
//   tipo_operacion: pago_proveedor | instalacion_cilindro
// No requiere auth: se recomienda validar por token de HA en el controlador
// -------------------------------------------------------------------------
Route::prefix('iot')->namespace('Api')->group(function () {
    Route::get('ping',                    'IotController@ping')->name('iot.ping');
    Route::get('proxima-tinaja',          'IotController@proximaTinaja')->name('iot.proxima-tinaja');
    Route::post('gas/registrar',          'GasIotController@registrar')->name('iot.gas.registrar');
    // Próxima reserva por tinaja — consumido por Home Assistant (sensor REST)
    Route::get('tinajas/proxima-reserva', 'TinajaController@proximaReserva')->name('iot.tinajas.proxima-reserva');
    // Próximas reservas de servicios (sauna, masaje container, masaje palmeras)
    Route::get('servicios/proximas-reservas', 'ServiciosIotController@proximasReservas')->name('iot.servicios.proximas-reservas');

    // Route::get('/iot/tinajas/proxima-reserva', 'Api\TinajaController@proximaReserva');
});

// -------------------------------------------------------------------------
// Egresos — Escaneo IA y registro rápido
// -------------------------------------------------------------------------
Route::prefix('egresos')->namespace('Api')->group(function () {
    // Datos para formulario (categorías, proveedores, etc.) — sin auth para facilitar integración
    Route::get('form-data',    'EgresoApiController@formData')->name('egresos.form-data');

    // Escaneo de factura/boleta con IA
    Route::post('scan',         'EgresoScanController@scan')->name('egresos.scan');
    Route::post('scan/confirm', 'EgresoScanController@confirm')->name('egresos.scan.confirm');

    // Ingreso rápido manual
    Route::post('/',            'EgresoApiController@store')->name('egresos.store');
    Route::get('/',             'EgresoApiController@index')->name('egresos.index');
    Route::get('/{id}',         'EgresoApiController@show')->name('egresos.show');
});

Route::prefix('woocommerce')->namespace('Api')->group(function(){

    // Endpoint de prueba — no requiere auth, solo verifica que el servidor responde
    Route::get('ping', 'WoocommerceWebhookController@ping')->name('woocommerce.ping');

    // Endpoint real del webhook (lo activaremos en el paso siguiente)
    Route::post('webhook', 'WoocommerceWebhookController@handle')
        ->name('woocommerce.webhook');
});

Route::middleware('auth.apikey')->group(function () {
    Route::get('/fechas-disponibles',       [FechasDisponiblesController::class,        'index']);
    Route::get('/verificar-disponibilidad', [VerificarDisponibilidadController::class, 'verificar']);
});


// -------------------------------------------------------------------------
// Bot WhatsApp / Instagram — Claude AI
// Protegido por X-Bot-Secret header (validado dentro del controlador)
// -------------------------------------------------------------------------
// Route::prefix('bot')->namespace('Api')->group(function () {
//     // Health check
//     Route::get('ping', 'BotController@ping')->name('bot.ping');

//     // Programas disponibles
//     Route::get('programas', 'BotProgramasController@index')->name('bot.programas');

//     // Disponibilidad por fecha
//     Route::get('disponibilidad', 'BotController@disponibilidad')->name('bot.disponibilidad');

//     // Cliente: buscar o crear
//     Route::post('clientes/buscar-o-crear', 'BotController@buscarOCrearCliente')->name('bot.clientes.buscarOCrear');

//     // Reservas
//     Route::post('reservas',          'BotController@crearReserva')->name('bot.reservas.store');
//     Route::post('reservas/{id}/pago', 'BotController@registrarPago')->name('bot.reservas.pago');
//     Route::post('reserva',           'BotReservaController@store')->name('bot.reserva');

//     // Conversación (estado/historial)
//     Route::get('conversacion/{usuario_id}', 'BotController@getConversacion')->name('bot.conversacion.get');
//     Route::post('conversacion',             'BotController@upsertConversacion')->name('bot.conversacion.upsert');

//     // ── Endpoint principal para n8n (Claude AI) ──
//     Route::post('message', 'BotController@message')->name('bot.message');
// });


// -------------------------------------------------------------------------
// Bot WhatsApp / Instagram — Claude AI (n8n)
// Protegido por X-Bot-Secret header (validado dentro del controlador)
// -------------------------------------------------------------------------
Route::prefix('bot-ai')->namespace('Api')->group(function () {
    Route::get('ping', 'BotController@ping')->name('bot-ai.ping');
    Route::get('programas', 'BotProgramasController@index')->name('bot-ai.programas');
    Route::get('disponibilidad', 'BotController@disponibilidad')->name('bot-ai.disponibilidad');
    Route::post('clientes/buscar-o-crear', 'BotController@buscarOCrearCliente')->name('bot-ai.clientes.buscarOCrear');
    Route::post('reservas', 'BotController@crearReserva')->name('bot-ai.reservas.store');
    Route::post('reservas/{id}/pago', 'BotController@registrarPago')->name('bot-ai.reservas.pago');
    Route::post('reserva', 'BotReservaController@store')->name('bot-ai.reserva');
    Route::get('conversacion/{usuario_id}', 'BotController@getConversacion')->name('bot-ai.conversacion.get');
    Route::post('conversacion', 'BotController@upsertConversacion')->name('bot-ai.conversacion.upsert');
    Route::post('message', 'BotController@message')->name('bot-ai.message');
});