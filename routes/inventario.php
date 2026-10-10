<?php

/*
| Inventario de insumos (kardex). Se carga desde RouteServiceProvider con
| middleware web + auth y nombres "backoffice.inventario.*".
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
