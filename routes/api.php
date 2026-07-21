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

// -------------------------------------------------------------------------
// IoT — Gas (Home Assistant)
// POST /api/iot/gas/registrar
//   tipo_operacion: pago_proveedor | instalacion_cilindro
// No requiere auth: se recomienda validar por token de HA en el controlador
// -------------------------------------------------------------------------
Route::prefix('iot')->namespace('Api')->group(function () {
    Route::post('gas/registrar',          'GasIotController@registrar')->name('iot.gas.registrar');
    // Próxima reserva por tinaja — consumido por Home Assistant (sensor REST)
    Route::get('tinajas/proxima-reserva', 'TinajaController@proximaReserva')->name('iot.tinajas.proxima-reserva');
    // Próximas reservas de servicios (sauna, masaje container, masaje palmeras)
    Route::get('servicios/proximas-reservas', 'ServiciosIotController@proximasReservas')->name('iot.servicios.proximas-reservas');
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

// -------------------------------------------------------------------------
// WhatsApp Webhook (Meta Cloud API)
// GET  /api/whatsapp/webhook  → verificación del webhook
// POST /api/whatsapp/webhook  → mensajes entrantes
// Sin middleware bot.token — Meta tiene su propio sistema de verificación
// -------------------------------------------------------------------------
Route::prefix('whatsapp')->namespace('Api')->group(function () {
    Route::get('webhook',  'WhatsAppWebhookController@verify')->name('whatsapp.webhook.verify');
    Route::post('webhook', 'WhatsAppWebhookController@receive')->name('whatsapp.webhook.receive');
});

// -------------------------------------------------------------------------
// Bot WhatsApp / Instagram — Claude AI
// Protegido por X-Bot-Secret header (validado dentro del controlador)
// -------------------------------------------------------------------------
// ─────────────────────────────────────────────────────────────────────────────
// Fintoc Webhook — verificación transferencias BancoEstado
// POST /api/fintoc/webhook
// Sin auth: validación por firma HMAC X-Fintoc-Signature
// ─────────────────────────────────────────────────────────────────────────────
Route::post('fintoc/webhook', 'PagoController@fintocWebhook')->name('fintoc.webhook');

Route::prefix('bot')->namespace('Api')->group(function () {
    // Health check
    Route::get('ping', 'BotController@ping')->name('bot.ping');

    // Programas disponibles
    Route::get('programas', 'BotProgramasController@index')->name('bot.programas');

    // Disponibilidad por fecha
    Route::get('disponibilidad', 'BotController@disponibilidad')->name('bot.disponibilidad');

    // Cliente: buscar o crear
    Route::post('clientes/buscar-o-crear', 'BotController@buscarOCrearCliente')->name('bot.clientes.buscarOCrear');

    // Reservas
    Route::post('reservas',          'BotController@crearReserva')->name('bot.reservas.store');
    Route::post('reservas/{id}/pago', 'BotController@registrarPago')->name('bot.reservas.pago');
    Route::post('reserva',                                    'BotReservaController@store')->name('bot.reserva');
    Route::patch('reserva/{id}/tipo-servicio',               'BotReservaController@updateTipoServicio')->name('bot.reserva.tipo_servicio');
    Route::patch('reserva/{id}/menu',                        'BotReservaController@updateMenu')->name('bot.reserva.menu');
    Route::get('menu-opciones',                              'BotReservaController@menuOpciones')->name('bot.menu.opciones');

    // Conversación (estado/historial)
    Route::get('conversacion/{usuario_id}', 'BotController@getConversacion')->name('bot.conversacion.get');
    Route::post('conversacion',             'BotController@upsertConversacion')->name('bot.conversacion.upsert');

    // ── Endpoint principal para n8n (Claude AI) ──
    Route::post('message', 'BotController@message')->name('bot.message');

    // ── Diagnóstico (solo dev) ──
    Route::get('diag',      'BotDiagController@index')->name('bot.diag');
    Route::get('slots',     'BotDiagController@slots')->name('bot.slots');
    Route::get('reset-qa',  'BotDiagController@resetQa')->name('bot.reset-qa');
});

