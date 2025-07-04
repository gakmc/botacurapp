<?php

namespace App\Http\Controllers;

use App\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $proveedores = Proveedor::all();

        return view('themes.backoffice.pages.proveedor.index', compact('proveedores'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('themes.backoffice.pages.proveedor.create');
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
            'rut' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser un texto.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',

            'rut.max' => 'El rut excede la cantidad de caracteres maximo.',
            
            'telefono.string' => 'El teléfono debe ser un texto.',
            'telefono.max' => 'El teléfono no puede exceder los 20 caracteres.',
            
            'correo.email' => 'El correo debe ser un email válido.',
            'correo.max' => 'El correo no puede exceder los 255 caracteres.',
        ]);

        Proveedor::create($request->all());

        return redirect()->route('backoffice.proveedor.index')->with('success', 'Proveedor creado correctamente.');
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
        $proveedor = Proveedor::findOrFail($id);

        return view('themes.backoffice.pages.proveedor.edit', compact('proveedor'));
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
        $proveedor = Proveedor::findOrFail($id);
        
        $request->validate([
            'nombre' => 'required|string|max:255',
            'rut' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser un texto.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',

            'rut.max' => 'El rut excede la cantidad de caracteres maximo.',
            
            'telefono.string' => 'El teléfono debe ser un texto.',
            'telefono.max' => 'El teléfono no puede exceder los 20 caracteres.',
            
            'correo.email' => 'El correo debe ser un email válido.',
            'correo.max' => 'El correo no puede exceder los 255 caracteres.',
        ]);

        $proveedor->update($request->all());

        return redirect()->route('backoffice.proveedor.index')->with('success', 'Proveedor creado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $proveedor = Proveedor::findOrFail($id);

        $proveedor->delete();

        return redirect()->route('backoffice.proveedor.index')->with('success','Proveedor eliminado de manera exitosa');
    }
}
