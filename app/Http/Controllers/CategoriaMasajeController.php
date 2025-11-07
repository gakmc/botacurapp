<?php

namespace App\Http\Controllers;

use App\CategoriaMasaje;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoriaMasajeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('themes.backoffice.pages.masaje.categoria.create');
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
        ],
        [
            'nombre.required' => 'El campo es requerido.',
            'nombre.max' => 'Excede el máximo de caractéres permitidos.',
        ]);

        $slug = Str::slug($request->nombre, '_');

        CategoriaMasaje::create([
            'nombre' => $request->nombre,
            'slug' => $slug,
        ]);

        return redirect()->route('backoffice.masajes.valores')->with('success','Categoria de masajes creada exitosamente');
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
        $categoria = CategoriaMasaje::findOrFail($id);

        return view("themes.backoffice.pages.masaje.categoria.edit", compact('categoria'));
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
        $categoria = CategoriaMasaje::findOrFail($id);
        $request->validate([
            'nombre' => 'required|string|max:255',
        ],
        [
            'nombre.required' => 'El campo es requerido.',
            'nombre.max' => 'Excede el máximo de caractéres permitidos.',
        ]);

        $slug = Str::slug($request->nombre, '_');

        $categoria->update([
            'nombre' => $request->nombre,
            'slug' => $slug,
        ]);

        return redirect()->route('backoffice.masajes.valores')->with('success','Categoria de masajes se actualizó exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
