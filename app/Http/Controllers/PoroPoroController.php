<?php

namespace App\Http\Controllers;

use App\PoroPoro;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class PoroPoroController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $productos = PoroPoro::all();

        return view('themes.backoffice.pages.poroporo.index', compact('productos'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('themes.backoffice.pages.poroporo.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'valor' => 'required|integer|min:0',
            'descripcion' => 'nullable|string|max:1500',
        ], [
            'nombre.required' => 'El campo es obligatorio.',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'nombre.max' => 'El campo nombre no debe exceder los 255 caracteres.',

            'valor.required' => 'El campo es obligatorio.',
            'valor.integer' => 'El campo valor debe ser un número entero.',
            'valor.min' => 'El valor debe ser al menos 0.',

            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no debe exceder los 1500 caracteres.',
        ]);

        PoroPoro::create([
            'nombre' => $request->nombre,
            'valor' => $request->valor,
            'descripcion' => $request->descripcion
        ]);

        Alert::success('Éxito', 'Se ha generado el producto')->showConfirmButton('Confirmar');
        return redirect()->route('backoffice.poroporo.index');
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
        $poroporo = PoroPoro::findOrFail($id);
        return view('themes.backoffice.pages.poroporo.edit', [
            'poro' => $poroporo
        ]);
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
        $request->validate([
            'nombre' => 'required|string|max:255',
            'valor' => 'required|integer|min:0',
            'descripcion' => 'nullable|string|max:1500',
        ], [
            'nombre.required' => 'El campo es obligatorio.',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'nombre.max' => 'El campo nombre no debe exceder los 255 caracteres.',

            'valor.required' => 'El campo es obligatorio.',
            'valor.integer' => 'El campo valor debe ser un número entero.',
            'valor.min' => 'El valor debe ser al menos 0.',

            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no debe exceder los 1500 caracteres.',
        ]);

        
        $poro = PoroPoro::findOrFail($id);

        $poro->update([
            'nombre' => $request->nombre,
            'valor' => $request->valor,
            'descripcion' => $request->descripcion,
        ]);


        Alert::success('Éxito', 'Se ha Modificado el producto')->showConfirmButton('Confirmar');
        return redirect()->route('backoffice.poroporo.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $producto = PoroPoro::findOrFail($id);

        $producto->delete();
        Alert::success('Eliminado', 'Se ha eliminado el producto')->showConfirmButton('Confirmar');
        return redirect()->route('backoffice.poroporo.index');
    }
}
