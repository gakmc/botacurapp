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

// Registro público deshabilitado: permitía a cualquiera crear una cuenta (con acceso
// también al backoffice). Los usuarios se crean desde la plataforma (UserController).
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
// Protegido por API key (header X-API-KEY o ?api_key=), validada por
// auth.apikey (ApiKeyMiddleware) contra config('app.laravel_api_key')
// [env LARAVEL_API_KEY]. /ping queda fuera de la protección para health-check.
// -------------------------------------------------------------------------
Route::prefix('iot')->namespace('Api')->middleware('auditoria:sistema,IoT Home Assistant')->group(function () {
    // Las lecturas para Home Assistant siguen sin API key (pendiente confirmar que HA la envía).
    // Los registros de gasto (gas/agua) crean egresos: exigen X-API-KEY (auth.apikey:iot).
    // Si HA aún no la envía, IOT_EXIGIR_API_KEY=false las acepta transitoriamente (queda en log y auditoría).
    Route::get('ping',                    'IotController@ping')->name('iot.ping');
    Route::get('proxima-tinaja',          'IotController@proximaTinaja')->name('iot.proxima-tinaja');
    Route::post('gas/registrar',          'GasIotController@registrar')->name('iot.gas.registrar')->middleware('auth.apikey:iot');
    Route::post('agua/registrar',         'AguaIotController@registrar')->name('iot.agua.registrar')->middleware('auth.apikey:iot');
    // Próxima reserva por tinaja — consumido por Home Assistant (sensor REST)
    Route::get('tinajas/proxima-reserva', 'TinajaController@proximaReserva')->name('iot.tinajas.proxima-reserva');
    // Agenda completa de hoy por tinaja/sauna — para mantener temperatura toda la jornada
    Route::get('tinajas/agenda-dia',      'TinajaController@agendaDia')->name('iot.tinajas.agenda-dia');
    // Estado / toggle del swap Tinaja 1 <-> Tinaja 2 (usado por el switch REST de HA)
    Route::get('tinajas/estado-inversion', 'TinajaController@estadoInversion')->name('iot.tinajas.estado-inversion');
    Route::post('tinajas/set-inversion',   'TinajaController@setInversion')->name('iot.tinajas.set-inversion');
    // Próximas reservas de servicios (sauna, masaje container, masaje palmeras)
    Route::get('servicios/proximas-reservas', 'ServiciosIotController@proximasReservas')->name('iot.servicios.proximas-reservas');
});

// -------------------------------------------------------------------------
// Egresos — Escaneo IA y registro rápido
// -------------------------------------------------------------------------
// Todo el grupo exige X-API-KEY (auth.apikey:egresos): crea y expone egresos.
// EGRESOS_EXIGIR_API_KEY=false lo deja pasar transitoriamente (queda en log y auditoría).
Route::prefix('egresos')->namespace('Api')->middleware(['auditoria:sistema,API de egresos', 'auth.apikey:egresos'])->group(function () {
    // Datos para formulario (categorías, proveedores, etc.) — incluye proveedores: también exige la API key
    Route::get('form-data',    'EgresoApiController@formData')->name('egresos.form-data');

    // Escaneo de factura/boleta con IA
    // Route::post('scan',         'EgresoScanController@scan')->name('egresos.scan'); // deshabilitada: reemplazada por integracion SII
    // Route::post('scan/confirm', 'EgresoScanController@confirm')->name('egresos.scan.confirm'); // deshabilitada: reemplazada por integracion SII

    // Ingreso rápido manual
    Route::post('/',            'EgresoApiController@store')->name('egresos.store');
    Route::get('/',             'EgresoApiController@index')->name('egresos.index');
    Route::get('/{id}',         'EgresoApiController@show')->name('egresos.show');
});

// -------------------------------------------------------------------------
// WhatsApp Business — webhook Meta
// GET  /api/whatsapp/webhook  → verificación
// POST /api/whatsapp/webhook  → mensajes entrantes
// -------------------------------------------------------------------------
Route::prefix('whatsapp')->namespace('Api')->middleware('auditoria:omitir')->group(function () {
    Route::get('webhook',  'WhatsAppWebhookController@verify')->name('whatsapp.webhook.verify');
    Route::post('webhook', 'WhatsAppWebhookController@handle')->name('whatsapp.webhook.handle');
});

Route::prefix('woocommerce')->namespace('Api')->middleware('auditoria:sistema,WooCommerce')->group(function(){

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
// Protegido por middleware bot.token (header X-Bot-Token == BOT_API_TOKEN)
// -------------------------------------------------------------------------
Route::prefix('bot-ai')->namespace('Api')->middleware('bot.token')->group(function () {
    Route::get('ping', 'BotController@ping')->name('bot-ai.ping');
    Route::get('programas', 'BotProgramasController@index')->name('bot-ai.programas');
    Route::get('disponibilidad', 'BotController@disponibilidad')->name('bot-ai.disponibilidad');
    Route::post('clientes/buscar-o-crear', 'BotController@buscarOCrearCliente')->name('bot-ai.clientes.buscarOCrear');
    Route::post('reservas', 'BotController@crearReserva')->name('bot-ai.reservas.store');
    Route::post('reservas/{id}/pago', 'BotController@registrarPago')->name('bot-ai.reservas.pago');
    Route::post('reservas/{id}/cancelar', 'BotController@cancelarReserva')->name('bot-ai.reservas.cancelar');
    Route::post('reservas/{id}/reprogramar', 'BotController@reprogramarReserva')->name('bot-ai.reservas.reprogramar');
    Route::post('reserva', 'BotReservaController@store')->name('bot-ai.reserva');
    Route::get('menu-opciones', 'BotController@menuOpciones')->name('bot-ai.menu-opciones');
    Route::patch('reserva/{id}/menu-seleccion', 'BotReservaController@guardarSeleccionMenu')->name('bot-ai.reserva.menu-seleccion');
    Route::get('conversacion/{usuario_id}', 'BotController@getConversacion')->name('bot-ai.conversacion.get');
    Route::post('conversacion', 'BotController@upsertConversacion')->name('bot-ai.conversacion.upsert');
    Route::post('message', 'BotController@message')->name('bot-ai.message');
});
// Cotizador para la tarea programada de correos (token COTIZADOR_API_TOKEN)
Route::prefix('cotizador')->namespace('Api')->middleware('cotizador.token')->group(function () {
    Route::get('catalogo', 'CotizadorController@catalogo')->name('cotizador.catalogo');
    Route::get('calendario', 'CotizadorController@calendario')->name('cotizador.calendario');
    Route::post('cotizaciones', 'CotizadorController@store')->name('cotizador.store');
    Route::get('cotizaciones/{id}/pdf', 'CotizadorController@pdf')->name('cotizador.pdf');
});


// Enlace firmado (sin token) para que el cliente descargue el PDF; vence a los 30 días
Route::get('cotizador/publico/{id}/pdf', 'Api\CotizadorController@pdfPublico')->name('cotizador.publico')->middleware('signed');
