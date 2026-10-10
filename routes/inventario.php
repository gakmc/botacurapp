<?php

/*
| Inventario de insumos (kardex) y ciclo de compras. Se carga desde
| RouteServiceProvider con middleware web + auth y nombres "backoffice.inventario.*".
*/

Route::get('/', 'InventarioController@index')->name('index');
Route::get('/toma', 'InventarioController@tomaCreate')->name('toma.create');
Route::post('/toma', 'InventarioController@tomaStore')->name('toma.store');
Route::get('/recepcion', 'InventarioController@recepcionCreate')->name('recepcion.create');
Route::post('/recepcion', 'InventarioController@recepcionStore')->name('recepcion.store');
Route::post('/merma', 'InventarioController@mermaStore')->name('merma.store');
Route::get('/movimientos', 'InventarioController@movimientos')->name('movimientos');
Route::get('/kardex/{insumo}', 'InventarioController@kardex')->name('kardex');
Route::get('/recetas', 'InventarioController@recetas')->name('recetas');
Route::post('/conciliar', 'InventarioController@conciliar')->name('conciliar');

// Ciclo de compras: lista -> pedido por proveedor -> compra -> recepción verificada
Route::get('/insumos', 'CompraInsumoController@configurar')->name('compras.configurar');
Route::post('/insumos', 'CompraInsumoController@guardarConfiguracion')->name('compras.configurar.guardar');
Route::get('/compras/lista', 'CompraInsumoController@lista')->name('compras.lista');
Route::post('/compras/pedidos', 'CompraInsumoController@crearPedidos')->name('compras.crear');
Route::get('/compras', 'CompraInsumoController@index')->name('compras.index');
Route::get('/compras/{pedido}', 'CompraInsumoController@show')->name('compras.show');
Route::post('/compras/{pedido}/cancelar', 'CompraInsumoController@cancelar')->name('compras.cancelar');
Route::get('/compras/{pedido}/comprar', 'CompraInsumoController@comprarForm')->name('compras.comprar');
Route::post('/compras/{pedido}/comprar', 'CompraInsumoController@comprarStore')->name('compras.comprar.store');
Route::get('/compras/{pedido}/recibir', 'CompraInsumoController@recibirForm')->name('compras.recibir');
Route::post('/compras/{pedido}/recibir', 'CompraInsumoController@recibirStore')->name('compras.recibir.store');
