<?php

namespace App\Http\Controllers;

use App\CategoriaCompra;
use App\Egreso;
use Illuminate\Http\Request;

class EgresoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $egresos = Egreso::with('categoria')->get();

        return view('themes.backoffice.pages.egreso.index', compact('egresos'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categorias = CategoriaCompra::all();

        return view('themes.backoffice.pages.egreso.create', compact('categorias'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->merge([
            'monto'    => (int) str_replace(['$', '.', ','], '', $request->monto),
        ]);

        $request->validate([
            'categoria_id' => 'required|exists:categorias_compras,id',
            'fecha'        => 'required|date',
            'monto'        => 'required|numeric|min:1',
        ],[
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists'   => 'La categoría seleccionada no es válida.',
            
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date'     => 'La fecha no tiene un formato válido.',

            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric'  => 'El monto debe ser un número.',
            'monto.min'      => 'El monto debe ser mayor a 0.',
        ]);

        Egreso::create($request->all());


        return redirect()->route('backoffice.egreso.index')->with('success', 'Egreso creado correctamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $egreso = Egreso::findOrFail($id);
        $categorias = CategoriaCompra::all();

        return view('themes.backoffice.pages.egreso.edit', compact('egreso', 'categorias'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $egreso = Egreso::findOrFail($id);

        $request->merge([
            'monto'    => (int) str_replace(['$', '.', ','], '', $request->monto),
        ]);

        $request->validate([
            'categoria_id' => 'required|exists:categorias_compras,id',
            'fecha'        => 'required|date',
            'monto'        => 'required|numeric|min:1',
        ],[
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists'   => 'La categoría seleccionada no es válida.',
            
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date'     => 'La fecha no tiene un formato válido.',

            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric'  => 'El monto debe ser un número.',
            'monto.min'      => 'El monto debe ser mayor a 0.',
        ]);

        $egreso->update($request->all());

        return redirect()->route('backoffice.egreso.index')->with('success', 'Egreso actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $egreso = Egreso::findOrFail($id);

        $egreso->delete();

        return redirect()->route('backoffice.egreso.index')->with('success', 'Egreso eliminado correctamente.');
    }
}
