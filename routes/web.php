<?php

use App\CategoriaCompra;
use App\Events\EjemploEvento;
use App\Sector;
use App\TipoDocumento;
use App\TipoProducto;
use App\TipoTransaccion;
use App\Ubicacion;
use App\UnidadMedida;
use App\Venta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

Route::get('/email', function () {
    // Simular una visita y una reserva para previsualización
    $reserva = App\Reserva::first(); // Usa un ejemplo de Reserva de tu base de datos
    $visita = $reserva->visitas; // Usa un ejemplo de Visita de tu base de datos
    $cliente = App\Cliente::first(); // Usa un ejemplo de Reserva de tu base de datos

    $programa = $reserva->programa;


    // Devolver la vista de correo
    return new App\Mail\RegistroReservaMailable($visita, $reserva, $cliente, $programa);
});



Route::get('/emitir', function () {
    broadcast(new EjemploEvento('Hola, WebSocket!'));
    return 'Evento emitido';
});


Route::get('test', function () {
    return 'hola';
})->middleware('role:anfitriona');

Route::get('/', function () {
    return view('welcome');
});

Route::get('home', function () {
    return view('home');
})->middleware('auth');

Auth::routes(['verify' => true]);

Route::group(['middleware' => ['auth'], 'as' => 'backoffice.'], function () {
    // Route::get('role', 'RoleController@index')->name('role.index');
    // Route::get('home','AdminController@show')->name('admin.show');
    Route::get('home', 'AdminController@show')->name('admin.show');
    Route::get('home/masajes', 'AdminController@index')->name('admin.index');
    Route::get('home/equipos', 'AdminController@team')->name('admin.team');
    
    Route::resource('user', 'UserController');
    Route::get('user/{user}/assign_role', 'UserController@assign_role')->name('user.assign_role');
    Route::post('user/{user}/role_assignment', 'UserController@role_assignment')->name('user.role_assignment');
    Route::get('user/{user}/assign_permission', 'UserController@assign_permission')->name('user.assign_permission');
    Route::post('user/{user}/permission_assignment', 'UserController@permission_assignment')->name('user.permission_assignment');

    // Metodos Reservas
    // Index - Mostrar una lista de reservas
    Route::get('reserva', 'ReservaController@index')->name('reserva.index');

    // Create - Ingresa al formulario para nueva reserva
    Route::get('reserva/create/{cliente}', 'ReservaController@create')->name('reserva.create');

    // Store - Guardar la nueva reserva
    Route::post('reserva', 'ReservaController@store')->name('reserva.store');

    // Show - Mostrar una reserva específica
    Route::get('reserva/{reserva}', 'ReservaController@show')->name('reserva.show');

    // Edit - Mostrar el formulario para editar una reserva
    // Route::get('reserva/{id}/edit', 'ReservaController@edit')->name('reserva.edit');

    Route::get('reservas', 'ReservaController@indexall')->name('reserva.listar');
    Route::get('reserva/{reserva}/edit', 'ReservaController@edit')->name('reserva.edit');

    // Update - Actualizar una reserva específica
    Route::put('reserva/{reserva}', 'ReservaController@update')->name('reserva.update');
    Route::delete('reserva/{reserva}', 'ReservaController@destroy')->name('reserva.destroy');
    Route::get('reserva/{reserva}/abono', 'ReservaController@showAbonoImage')->name('reserva.abono.imagen');
    Route::get('reserva/{reserva}/diferencia', 'ReservaController@showDiferenciaImage')->name('reserva.diferencia.imagen');
    Route::get('reserva/{reserva}/consumo', 'ReservaController@showConsumoImage')->name('reserva.consumo.imagen');

    // Metodos Complementos CREAR
    Route::get('sectores/create', function () {
        return view('themes.backoffice.pages.sector.create');
    })->name('sectores.create');

    Route::get('ubicaciones/create', function () {
        return view('themes.backoffice.pages.ubicacion.create');
    })->name('ubicaciones.create');

    Route::get('unidades_medidas/create', function () {
        return view('themes.backoffice.pages.unidad_medida.create');
    })->name('unidades_medidas.create');

    Route::get('tipo_documentos/create', function () {
        return view('themes.backoffice.pages.tipo_documento.create');
    })->name('tipo_documentos.create');

    Route::get('tipo_transacciones/create', function () {
        return view('themes.backoffice.pages.tipo_transaccion.create');
    })->name('tipo_transacciones.create');

    Route::get('categoria_compras/create', function () {
        return view('themes.backoffice.pages.categoria_compra.create');
    })->name('categoria_compras.create');

    Route::get('tipo_productos/create', function () {
        $sectores = Sector::all();
        return view('themes.backoffice.pages.tipo_producto.create', compact('sectores'));
    })->name('tipo_productos.create');

    // Metodos Complementos EDITAR
    Route::get('sector/{id}/edit', function ($id) {

        $sector = Sector::findOrFail($id);
        return view('themes.backoffice.pages.sector.edit', compact('sector'));

    })->name('sector.edit');

    Route::get('ubicacion/{id}/edit', function ($id) {

        $ubicacion = Ubicacion::findOrFail($id);
        return view('themes.backoffice.pages.ubicacion.edit', compact('ubicacion'));

    })->name('ubicacion.edit');

    Route::get('unidad_medida/{id}/edit', function ($id) {
        $unidad = UnidadMedida::findOrFail($id);
        return view('themes.backoffice.pages.unidad_medida.edit', compact('unidad'));
    })->name('unidad_medida.edit');

    Route::get('tipo_documento/{id}/edit', function ($id) {
        $documento = TipoDocumento::findOrFail($id);
        return view('themes.backoffice.pages.tipo_documento.edit', compact('documento'));
    })->name('tipo_documento.edit');

    Route::get('tipo_transaccion/{id}/edit', function ($id) {
        $transaccion = TipoTransaccion::findOrFail($id);
        return view('themes.backoffice.pages.tipo_transaccion.edit', compact('transaccion'));
    })->name('tipo_transaccion.edit');

    Route::get('categoria_compras/{id}/edit', function ($id) {
        $categoria = CategoriaCompra::findOrFail($id);
        return view('themes.backoffice.pages.categoria_compra.edit', compact('categoria'));
    })->name('categoria_compras.edit');

    Route::get('tipo_producto/{id}/edit', function ($id) {
        $producto = TipoProducto::findOrFail($id);
        return view('themes.backoffice.pages.tipo_producto.edit', compact('producto'));
    })->name('tipo_producto.edit');

    // PDF
    Route::get('/generar-pdf/{reserva}', 'ClienteController@generarPDF')->name('cliente.pdf');

    Route::get('/ver-pdf/{reserva}', 'ReservaController@generarPDF')->name('venta.pdf');

    Route::get('/pdf-consumo/{reserva}', 'ReservaController@generarPDFConsumo')->name('consumo.pdf');

    //Fin PDF

    Route::get('venta/{ventum}/verconsumo', 'VentaController@verconsumo')->name('reserva.venta.verconsumo');

    
    Route::get('reserva/{reserva}/venta/{ventum}/cerrar', 'VentaController@cerrar')->name('reserva.venta.cerrar');
    Route::match(['put', 'patch'],'reserva/{reserva}/venta/{ventum}/cerrarventa', 'VentaController@cerrarventa')->name('reserva.venta.cerrarventa');
    
    // Metodos Reservas
    // Index - Mostrar una lista de reservas
    Route::get('venta/{venta}/consumo/ingresar', 'ConsumoController@service_create')->name('venta.consumo.service_create');
    // Route::get('reserva/{reserva}/diferencia', 'ReservaController@showDiferenciaImage')->name('reserva.diferencia.imagen');
    
    // Create - Ingresa al formulario para nueva reserva
    // Route::get('reserva/create/{cliente}', 'ReservaController@create')->name('reserva.create');

    Route::get('/verificar-ubicaciones', 'ReservaController@verificarUbicaciones')->name('verificar.ubicaciones');

    // Borrar en caso de no utilizar
    // Route::post('/verificar-horarios', 'VisitaController@obtenerHorariosDisponibles')->name('verificar.horarios');

    // Store - Guardar la nueva reserva
    Route::post('venta/{venta}/consumo/registrar', 'ConsumoController@service_store')->name('venta.consumo.service_store');
    
    Route::get('visita/{visitum}/ubicacion_edit', 'VisitaController@edit_ubicacion')->name('visita.edit_ubicacion');
    Route::match(['put', 'patch'],'visita/{visitum}/ubicacion', 'VisitaController@update_ubicacion')->name('visita.update_ubicacion');


    // Show - Mostrar una reserva específica
    // Route::get('reserva/{reserva}', 'ReservaController@show')->name('reserva.show');

    Route::get('sueldos/{user}','SueldoController@view')->name('sueldo.view');
    Route::get('sueldo/{user}','SueldoController@view_maso')->name('sueldo.view_maso');

    Route::get('/actualizar-sueldo-base','SueldoController@actualizarSueldoBase');

    Route::post('sueldo/masoterapeuta','SueldoController@store_maso')->name('sueldo.store_maso');
    
    Route::post('barman/detalles-consumos/{id}/actualizar-estado', 'BarmanController@actualizarEstado')->name('barman.actualizar_estado');
    Route::post('barman/bebidas/detalles-consumos/{id}/actualizar-estado', 'BarmanController@actualizarEstado')->name('barman.actualizar_estado');
    Route::get('/barman/bebidas', 'BarmanController@bebidas')->name('barman.bebidas');

    
    Route::resource('asignacion', 'AsignacionController');
    Route::resource('barman', 'BarmanController');
    Route::resource('cliente', 'ClienteController');
    Route::resource('complemento', 'ComplementoController');
    Route::resource('insumo', 'InsumoController');
    Route::resource('masaje', 'MasajeController');
    Route::resource('menu', 'MenuController');
    Route::resource('permission', 'PermissionController');
    Route::resource('producto', 'ProductoController');
    Route::resource('programa', 'ProgramaController');
    Route::resource('reserva.reagendamientos', 'ReagendamientoController');
    Route::resource('reserva.venta', 'VentaController');
    Route::resource('reserva.visitas', 'VisitaController');
    Route::resource('role', 'RoleController');
    Route::resource('servicio', 'ServicioController');
    Route::resource('sueldos', 'SueldoController');
    Route::resource('venta.consumo', 'ConsumoController');
    Route::resource('visita', 'VisitaController');
});
