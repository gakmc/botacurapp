<?php

namespace App\Http\Controllers;

use App\CategoriaMasaje;
use App\TipoMasaje;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TipoMasajeController extends Controller
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
        $categorias = CategoriaMasaje::all();
        return view('themes.backoffice.pages.masaje.tipo.create', compact(['categorias']));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'nombre' => 'required|string|max:255',
            'id_categoria_masaje' => 'required|exists:categorias_masajes,id'
        ],
        [
            'nombre.required' => 'El campo es requerido.',
            'nombre.max' => 'Excede el máximo de caractéres permitidos.',
            'id_categoria_masaje.required' => 'El campo es requerido.',
            'id_categoria_masaje.exists' => 'El valor ingresado no existe.',
        ]);

        // dd($request->all());

        $slug = Str::slug($request->nombre, '_');

        $tipoMasaje = TipoMasaje::create([
            'id_categoria_masaje' => $request->id_categoria_masaje,
            'nombre' => $request->nombre,
            'slug' => $slug,
            'activo' => true,
        ]);

        return redirect()->route('backoffice.masajes.valores')->with('masaje_creado',$tipoMasaje->id);
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
        $tipo = TipoMasaje::findOrFail($id);
        $categorias = CategoriaMasaje::all();
        return view("themes.backoffice.pages.masaje.tipo.edit", compact('tipo', 'categorias'));
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
        $tipoMasaje = TipoMasaje::findOrFail($id);
        $request->validate([
            'nombre' => 'required|string|max:255',
            'id_categoria_masaje' => 'required|exists:categorias_masajes,id'
        ],
        [
            'nombre.required' => 'El campo es requerido.',
            'nombre.max' => 'Excede el máximo de caractéres permitidos.',
            'id_categoria_masaje.required' => 'El campo es requerido.',
            'id_categoria_masaje.exists' => 'El valor ingresado no existe.',
        ]);

        $slug = Str::slug($request->nombre, '_');

        $tipoMasaje->update([
            'id_categoria_masaje' => $request->id_categoria_masaje,
            'nombre' => $request->nombre,
            'slug' => $slug,
            'activo' => true,
        ]);

        return redirect()->route('backoffice.masajes.valores')->with('success',"Tipo masaje actualizado exitosamente.");
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
