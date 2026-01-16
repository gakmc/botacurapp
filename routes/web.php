<?php

use App\CategoriaCompra;
use App\Events\EjemploEvento;
use App\Http\Controllers\EmailPreviewController;
use App\Reserva;
use App\Sector;
use App\TipoDocumento;
use App\TipoProducto;
use App\TipoTransaccion;
use App\Ubicacion;
use App\UnidadMedida;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Illuminate\Support\Facades\Mail;

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


Route::get('/test-mail', function () {
    $data = [
        'pdfPath' => storage_path('app/temp_pdfs/test.pdf'), // crea un archivo dummy si quieres
        'correo'  => 'tu_correo@ejemplo.com',
    ];

    // pruébalo con un mailable simple sin adjunto primero
    Mail::raw('Prueba de correo desde Laravel', function ($message) {
        $message->to('tu_correo@ejemplo.com')
                ->subject('Test mail simple');
    });

    return 'ok';
});

Route::get('/prueba-pdf', function () {
    $pdf = PDF::loadHTML('<h1>Hola desde wkhtmltopdf</h1>');
    return $pdf->inline('test.pdf'); // o ->download('test.pdf')
});


Route::get('/prueba-certificado', function () {
    $pdf = PDF::loadHTML('
    <head>
        < meta charset = "utf-8" >
        < style >

        body {
        font - family: Arial, Helvetica, sans - serif;
        font - size: 12px;
        margin: 30px;
        margin - top: 0px;
        }
        . title {text - align: center;
        font - size: 18px;
        font - weight: bold;
        margin - top: 10px;}
        . box {
        margin - top: 20px;
        line - height: 1.7;
        text - align: justify; /* ayuda al corte */
        }

        . box li {
        margin - bottom: 8px;
        }

        ol {margin: 0;
        padding - left: 18px;}
        ul {margin: 6px000;
        padding - left: 18px;}

        ol > li {margin - bottom: 12px;}
        ul > li {margin - bottom: 6px;}

        . box listrong {
        display: inline - block; /* 👈 CLAVE */
        margin - right: 4px;
        }

        . box lispan {
        display: inline;
        }

        . firma {margin - top: 60px;
        text - align: center;}
        . small {margin - top: 25px;
        font - size: 10px;
        color: #555; }

        . header {
            text - align: center;
            margin - bottom: 30px;
        }

        . logo {
            max - height: 120px;
        }

        . info - table,  . items - table {
            width: 100 % ;
            border - collapse: collapse;
            margin - top: 20px;
        }

        . info - table td {
            padding: 4px0;
        }

        . items - table th,  . items - table td {
            border: 1px solid#ccc;
            padding: 8px;
        }

        . items - table th {
            background - color: #f2f2f2;
        }

        . text - right {
            text - align: right;
        }

        . text - left {
            text - align: left;
        }

        . highlight {
            color: #039B7B;
            font - weight: bold;
        }

        . enlaces {
            color: #777;
            text - decoration: none;
        }

        @page {
            margin: 50px30px;
            footer: footer;
        }
        <  / style >

    </head>
    <body>
        <div class="header">
            <img src="https://botacura.cl/wp-content/uploads/2024/04/logo.png" alt="Logo Botacura" class="logo">
            <p>Cam. Al Volcán 13274, El Manzano, San José de Maipo, Región Metropolitana</p>
            <p>Centro de relajación y descanso</p>
            <h2 class="highlight title">CERTIFICADO DE FUNCIONES Y COMPETENCIAS LABORALES</h2>
        </div>


        <div class="box">
            Quien suscribe, en representación de <strong>Botacura</strong>. 
            <br><br>
            Hace constar que el <strong>Sr. Gabriel Villar Vera</strong> se desempeña en el área de Tecnología de la Información, cumpliendo con excelencia las siguientes responsabilidades técnicas:

                <br>
                <br>
            <ol>
                <li type="number">
                    <strong>Desarrollo y Mantenimiento Web</strong>
                
                    <ul>
                        <li>
                        <strong>Ciclo de Vida de Software:</strong> 
                        <span>Liderazgo en la creación, desarrollo evolutivo y mantenimiento preventivo/correctivo de la plataforma web institucional.</span>
                        </li>

                        <li>
                            <strong>Optimización:</strong> 
                            <span>Implementación de mejoras funcionales y de interfaz para asegurar la escalabilidad del sitio.</span>
                        </li>
                    </ul>
                </li>
                <br>

                <li type="number">
                    <strong>Administración de Infraestructura Cloud (AWS)</strong>
                    <ul>
                    <li><strong>Gestión de Servidores:</strong> <span>Administración integral de instancias en Amazon EC2, incluyendo configuración, monitoreo de rendimiento y despliegue de servicios.</span></li>
                    <li><strong>Gestión de Bases de Datos:</strong> <span>Diseño, administración y optimización de bases de datos para garantizar la integridad y disponibilidad de la información.</span></li>
                    </ul>
                    </li>
                <br>

                <li type="number">
                <strong>Gestión de Servicios y Soporte Técnico</strong>
                <ul>
                <li><strong>Administración de Correo:</strong> <span>Configuración y gestión de cuentas de correo corporativo y servicios de mensajería.</span></li>
                <li><strong>Capacitación Técnica:</strong> <span>Formación y apoyo a los usuarios finales en el uso correcto y eficiente de la plataforma web y herramientas digitales del centro.</span></li>
                </ul>
                </li>
                </ol>
                </div>
                
                <div class="firma">
                <strong>Observaciones:</strong> El Sr. Villar Vera ha demostrado un alto dominio de herramientas tecnologicas, capacidad de resolución de problemas y una notable disposición para la colaboración al equipo.
                
                
                </div>
                <br><br>
                <div class="box">
                    Se extiende el presente certificado, para los fines que el interesado estime conveniente.
                </div>

                <div class="firma">
            ___________________________<br>
            Firma y timbre <br>


            Sebastian Wimmer Wirlok - Administrador - Botacura - +56 9 6191 0398
        </div>
    </body>
    ');
    return $pdf->stream('Certificado_de_funciones_Gabriel_Villar.pdf'); // o ->download('test.pdf')
});



Route::get('/email', [EmailPreviewController::class, 'preview']);

Route::get('/emitir', function () {
    event(new EjemploEvento('Hola, WebSocket!'));
    return 'Evento emitido';
});

Route::get('test', function () {
    return view('test');
});

Route::get('avisos-cocina', function () {
    $reservasRaw = Reserva::with([
        'cliente',
        'programa',
        'visitas',
        'menus' => function ($query) {
            $query->with(['productoEntrada', 'productoFondo', 'productoAcompanamiento']);
        },
    ])
        ->where('avisado_en_cocina', 'avisado')
        ->whereDate('fecha_visita', today())
        ->orderBy('fecha_visita', 'desc')
        ->get();

    $reservas = $reservasRaw->groupBy(function ($reserva) {
        return \Carbon\Carbon::parse($reserva->fecha_visita)->format('d-m-Y');
    });

    return view('platos-avisados', compact('reservas'));
});


Route::get('error', function () {
    return view('errors.404');
});

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

        // Metodos Reservas
    // Index - Mostrar una lista de reservas
    Route::get('venta/{venta}/consumo/ingresar_servicio', 'ConsumoController@service_create')->name('venta.consumo.service_create');
    // Store - Guardar la nueva reserva
    Route::post('venta/{venta}/consumo/registrar', 'ConsumoController@service_store')->name('venta.consumo.service_store');

    Route::get('giftcards/verificar', 'GiftCardController@verificarCodigo')->name('giftcards.verificar');

    Route::get('giftcards/lista', 'GiftCardController@listaCodigos')->name('giftcards.lista');


    Route::get('/producto/inactivos', 'ProductoController@index_inactivos')->name('producto.inactivos');
    Route::patch('/producto/{producto}/estado', 'ProductoController@cambiarEstado')->name('producto.estado');
    

    Route::resource('usuario-sueldo', 'AnularSueldoUsuarioController');
    Route::resource('asignacion', 'AsignacionController');
    Route::resource('asistencia', 'AsistenciaController');
    Route::resource('barman', 'BarmanController');
    Route::resource('categoria-masaje', 'CategoriaMasajeController');
    Route::resource('cliente', 'ClienteController');
    Route::resource('complemento', 'ComplementoController');
    Route::resource('cotizacion', 'CotizacionController');
    Route::resource('egreso', 'EgresoController');
    Route::resource('estado_recepcion', 'EstadoRecepcionController');
    Route::resource('giftcards', 'GiftCardController');
    Route::resource('insumo', 'InsumoController');
    Route::resource('masaje', 'MasajeController');
    Route::resource('menu', 'MenuController');
    Route::resource('permission', 'PermissionController');
    Route::resource('poro-pagado', 'PoroPagadoController');
    Route::resource('poroporo', 'PoroPoroController');
    Route::resource('producto', 'ProductoController');
    Route::resource('programa', 'ProgramaController');
    Route::resource('proveedor', 'ProveedorController');
    Route::resource('rango-sueldos', 'RangoSueldoRoleController');
    Route::resource('reserva.reagendamientos', 'ReagendamientoController');
    Route::resource('reserva.venta', 'VentaController');
    Route::resource('reserva.visitas', 'VisitaController');
    Route::resource('role', 'RoleController');
    Route::resource('servicio', 'ServicioController');
    Route::resource('subcategoria', 'SubcategoriaController');
    Route::resource('sueldos', 'SueldoController');
    Route::resource('sueldo-pagado', 'SueldoPagadoController');
    Route::resource('tipo-masaje', 'TipoMasajeController');
    Route::resource('tipo-masaje', 'TipoMasajeController');
    Route::resource('user', 'UserController');
    Route::resource('venta.consumo', 'ConsumoController');
    Route::resource('venta_directa', 'VentaDirectaController');
    Route::resource('ventas_poroporo', 'PoroPoroVentaController');
    Route::resource('visita', 'VisitaController');



    Route::get('home', 'AdminController@show')->name('admin.show');
    Route::get('home/masajes', 'AdminController@index')->name('admin.index');
    Route::get('home/equipos', 'AdminController@team')->name('admin.team');
    Route::get('home/consumos', 'AdminController@consumos')->name('admin.consumos');
    Route::get('home/menu', 'AdminController@menuMovil')->name('admin.menu');
    Route::get('consumos/{anio}/{mes}', 'AdminController@consumosMensuales')->name('admin.consumos.detalleMes');
    Route::get('servicios/{anio}/{mes}', 'AdminController@serviciosMensuales')->name('admin.servicios.detalleMes');
    Route::get('home/ingresos', 'AdminController@ingresos')->name('admin.ingresos');
    Route::get('ingresos/{anio}/{mes}', 'AdminController@detalleMes')->name('admin.ingresos.detalleMes');
    Route::get('ingresos/{anio}/{mes}/{dia}', 'AdminController@ingresosDiarios')->name('admin.ingresos.detalleDia');
    Route::get('cierre-caja/{anio}/{mes}/{dia}', 'AdminController@cierreCaja')->name('admin.cierreCaja');
    Route::get('user/{user}/assign_role', 'UserController@assign_role')->name('user.assign_role');
    Route::get('user/{user}/assign_permission', 'UserController@assign_permission')->name('user.assign_permission');
    Route::get('reserva', 'ReservaController@index')->name('reserva.index');

    // NUEVA: contenido (HTML) para cargar por JS
    Route::get('/reserva/contenido', 'ReservaController@contenido')->name('reserva.contenido');

    Route::get('reserva/create/{cliente}', 'ReservaController@create')->name('reserva.create');


    
    // Metodos Reservas
    // Index - Mostrar una lista de reservas




    Route::post('user/{user}/role_assignment', 'UserController@role_assignment')->name('user.role_assignment');
    Route::post('user/{user}/permission_assignment', 'UserController@permission_assignment')->name('user.permission_assignment');
    
    // Create - Ingresa al formulario para nueva reserva
    Route::post('/validar-whatsapp', 'ClienteController@validarWhatsapp')->name('validar.whatsapp');
    Route::post('/validar-whatsapp-edit', 'ClienteController@validarWhatsappEdit')->name('validar.whatsapp.edit');
    
    // Store - Guardar la nueva reserva
    Route::post('reserva', 'ReservaController@store')->name('reserva.store');
    
    // Show - Mostrar una reserva específica
    Route::get('reserva/{reserva}', 'ReservaController@show')->name('reserva.show');
    
    // Edit - Mostrar el formulario para editar una reserva
    // Route::get('reserva/{id}/edit', 'ReservaController@edit')->name('reserva.edit');
    
    Route::get('reservas', 'ReservaController@indexall')->name('reservas.listar');
    
    Route::get('reservas/registro', 'ReservaController@indexReserva')->name('reservas.registro');
    
    Route::get('reservas/registros', 'ReservaController@indexallRegistros')->name('reservas.registros');

    Route::get('/reservas/eventos', 'ReservaController@eventosCalendar')
    ->name('reservas.eventos');


    Route::get('/asignaciones/eventos', 'AsignacionController@eventosCalendar')
    ->name('asignacion.eventos');



    // Cocina (Menús)
    Route::get('/cocina', 'MenuController@index')->name('cocina.index');
    Route::get('/cocina/dia', 'MenuController@dia')->name('cocina.dia'); // JSON



    
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
    
    Route::get('cotizacion/{cotizacion}/ver', 'CotizacionController@visualizarPDF')->name('cotizacion.verpdf');
    
    Route::post('cotizacion/{cotizacion}/enviar', 'CotizacionController@enviarPDF')->name('cotizacion.enviarpdf');
    
    //Fin PDF
    
    Route::get('venta/{ventum}/verconsumo', 'VentaController@verconsumo')->name('reserva.venta.verconsumo');
    
    Route::get('venta/cierre_ventas', 'VentaController@index_cierre')->name('reserva.venta.cierre');
    
    Route::get('reserva/{reserva}/venta/{ventum}/cerrar', 'VentaController@cerrar')->name('reserva.venta.cerrar');
    Route::match(['put', 'patch'], 'reserva/{reserva}/venta/{ventum}/cerrarventa', 'VentaController@cerrarventa')->name('reserva.venta.cerrarventa');
    

    // Route::get('reserva/{reserva}/diferencia', 'ReservaController@showDiferenciaImage')->name('reserva.diferencia.imagen');
    
    // Create - Ingresa al formulario para nueva reserva
    // Route::get('reserva/create/{cliente}', 'ReservaController@create')->name('reserva.create');
    
    Route::get('/verificar-ubicaciones', 'ReservaController@verificarUbicaciones')->name('verificar.ubicaciones');
    
    // Borrar en caso de no utilizar
    // Route::post('/verificar-horarios', 'VisitaController@obtenerHorariosDisponibles')->name('verificar.horarios');
    

    
    Route::get('visita/{visitum}/ubicacion_edit', 'VisitaController@edit_ubicacion')->name('visita.edit_ubicacion');
    Route::match(['put', 'patch'], 'visita/{visitum}/ubicacion', 'VisitaController@update_ubicacion')->name('visita.update_ubicacion');
    
    Route::get('reserva/{reserva}/visita/{visita}/register', 'VisitaController@register')->name('reserva.visita.register');
    Route::match(['put', 'patch'], 'reserva/{reserva}/visita/{visita}/register_update', 'VisitaController@register_update')->name('reserva.visita.register_update');
    
    Route::get('reserva/{reserva}/menus', 'ReservaController@menu')->name('reserva.menus');
    
    Route::match(['put', 'patch'], 'reserva/{reserva}/menu_update', 'ReservaController@menu_update')->name('reserva.menu_update');
    
    Route::get('reserva/{reserva}/masajes', 'ReservaController@masaje')->name('reserva.masajes');
    Route::match(['put', 'patch'], 'reserva/{reserva}/masaje_update', 'ReservaController@masaje_update')->name('reserva.masaje_update');
    
    Route::post('/masajes/asignar_multiples', 'MasajeController@asignar_multiples')->name('masaje.asignar_multiples');
    
    Route::get('masajes/valores', 'MasajeController@index_valor')->name('masajes.valores');
    Route::get('masajes/valores/create', 'MasajeController@valor_masaje_create')->name('masajes.valores.create');
    Route::post('masajes/valores/store', 'MasajeController@valor_masaje_store')->name('masajes.valores.store');

    Route::get('masajes/valores/{valor}/edit', 'MasajeController@valor_masaje_edit')->name('masajes.valores.edit');
    Route::match(['put', 'patch'], 'masajes/valores/{precio}/update', 'MasajeController@valor_masaje_update')->name('masajes.valores.update');

    Route::get('/masajes/valores/inactivos', 'MasajeController@index_valor_inactivos')->name('masajes.valores.inactivos');
    Route::patch('/masajes/{tipoMasaje}/estado', 'MasajeController@cambiarEstado')->name('masajes.estado');
    
    Route::post('boleta/reserva/{reserva}', 'BoletaController@databoleta')->name('boleta.reserva');
    
    Route::post('boleta/venta_directa/{venta_directa}', 'BoletaController@databoletaventadirecta')->name('boleta.venta_directa');
    Route::post('boleta/poro_poro/{poroVenta}', 'BoletaController@databoletaventaporoporo')->name('boleta.poro_poro');
    
    Route::get('reserva/{reserva}/visita/{visita}/spa', 'VisitaController@spa')->name('reserva.visitas.spa');
    Route::match(['put', 'patch'], 'reserva/{reserva}/visita/{visita}/spa_update', 'VisitaController@spa_update')->name('reserva.visitas.spa_update');
    
    // Show - Mostrar una reserva específica
    // Route::get('reserva/{reserva}', 'ReservaController@show')->name('reserva.show');
    
    Route::get('sueldos/{user}/{anio}/{mes}', 'SueldoController@adminViewSueldos')->name('sueldo.view.admin');
    Route::get('sueldos/{user}/{anio}/{mes}/{dia}', 'SueldoController@detalle_diario')->name('sueldo.view.diario');
    Route::get('sueldo/{user}', 'SueldoController@view')->name('sueldo.view');
    Route::get('sueldo/masoterapeuta/{user}', 'SueldoController@view_maso')->name('sueldo.view_maso');
    
    Route::get('/actualizar-sueldo-base', 'SueldoController@actualizarSueldoBase');
    
    Route::post('sueldo/masoterapeuta', 'SueldoController@store_maso')->name('sueldo.store_maso');
    
    Route::post('barman/detalles-consumos/{id}/actualizar-estado', 'BarmanController@actualizarEstado')->name('barman.actualizar_estado');
    Route::post('barman/bebidas/detalles-consumos/{id}/actualizar-estado', 'BarmanController@actualizarEstado')->name('barman.actualizar_estado');
    
    
    Route::get('/barman/bebidas', 'BarmanController@bebidas')->name('barman.bebidas');
    Route::get('/subcategorias/{categoria_id}', 'SubcategoriaController@getByCategoria')->name('subcategoria.categoria');
    
    
    
    
    
    Route::put('/avisar-cocina/{reserva}', 'ReservaController@avisarCocina')->name('reserva.avisar');
    Route::put('/entregar-menu/{reserva}', 'ReservaController@entregarMenu')->name('reserva.entregar');
    
    // Rutas Delete para eliminar detalle de consumo
    Route::delete('/consumo/detalle/{tipo}/{id}', 'ConsumoController@destroyDetalle')->name('consumo.detalle.destroy');
    
    
    
    Route::get('/egreso/{anio}/{mes}', 'EgresoController@index_mes')->name('egreso.mes');
    
    
    Route::post('/egreso/pago_fijo', 'EgresoController@pago_fijo')->name('egreso.pago_fijo');
    
    Route::post('/egreso/pago_variable', 'EgresoController@pago_variable')->name('egreso.pago_variable');
    


    Route::match(['put', 'patch'], '/egreso/{egreso}/update_variable', 'EgresoController@update_variable')->name('egreso.update_variable');
    
    Route::get('finanzas/resumen-anual', 'ReporteFinancieroController@resumenAnual')->name('finanzas.resumen.anual');

    Route::get('finanzas/resumen/{anio}/{mes}', 'ReporteFinancieroController@resumenMensual')->name('finanzas.resumen.mensual');

    Route::get('finanzas/ingresos', 'ReporteFinancieroController@ingresosPercibidos')->name('finanzas.ingresos_percibidos');

    Route::get('finanzas/ingresos/comparar', 'ReporteFinancieroController@comparar')->name('finanzas.comparar');


    Route::get('giftcards/{gc}/enviarpdf', 'GiftCardController@enviarpdf')->name('giftcards.enviar');
    Route::get('giftcards/{gc}/reservar', 'GiftCardController@byPassReserva')->name('giftcards.reservar');


    Route::get('informes', 'InformeController@index')->name('informes.index');
    Route::get('/graficos/bebestibles-mensuales', 'InformeController@bebestiblesMensuales')->name('informes.bebestibles');
    Route::get('/graficos/programas-mensuales', 'InformeController@programasMensuales')->name('informes.programas');




    Route::get('/certificados/antiguedad/{user}', 'CertificadoController@create')->name('certificados.antiguedad.create');
    Route::post('/certificados/antiguedad/{user}', 'CertificadoController@store')->name('certificados.antiguedad.store');


});
