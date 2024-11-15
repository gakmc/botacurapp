<?php

namespace App\Http\Controllers;

use App\Http\Requests\Servicio\StoreRequest;
use App\Http\Requests\Servicio\UpdateRequest;
use App\Servicio;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class ServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {$servicios = Servicio::all();
        return view('themes.backoffice.pages.servicio.index', compact('servicios'));}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('themes.backoffice.pages.servicio.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        $servicio = new Servicio();
        $servicio->nombre_servicio = $request->input('nombre_servicio');
        $servicio->valor_servicio = $request->input('valor_servicio');
        $servicio->costo_servicio = $request->input('costo_servicio');
        $servicio->duracion = $request->input('duracion');
        $servicio->save();

        Alert::success('Éxito', 'Se ha Almacenado el servicio correctamente')->showConfirmButton('Confirmar');
        return redirect()->route('backoffice.servicio.show', $servicio);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Servicio  $servicio
     * @return \Illuminate\Http\Response
     */
    public function show(Servicio $servicio)
    {
        return view('themes.backoffice.pages.servicio.show', compact('servicio'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Servicio  $servicio
     * @return \Illuminate\Http\Response
     */
    public function edit(Servicio $servicio)
    {
        return view('themes.backoffice.pages.servicio.edit', compact('servicio'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Servicio  $servicio
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Servicio $servicio)
    {
        $servicio = Servicio::findOrFail($servicio->id);
        // asignar el servicio actual con el valor anterior
        $valorServicioAnterior = $servicio->valor_servicio;

        $servicio->nombre_servicio = $request->input('nombre_servicio');
        $servicio->valor_servicio = $request->input('valor_servicio');
        $servicio->costo_servicio = $request->input('costo_servicio');
        $servicio->duracion = $request->input('duracion');
        $servicio->save();

        foreach ($servicio->programas as $programa) {
            //Restar valor anterior
            $programa->valor_programa -= $valorServicioAnterior;

            //Sumar nuevo Valor
            $programa->valor_programa += $servicio->valor_servicio;

            $programa->save();
        }

        Alert::success('Éxito', 'Se ha Actualizado el servicio correctamente')->showConfirmButton('Confirmar');
        return redirect()->route('backoffice.servicio.show', $servicio);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Servicio  $servicio
     * @return \Illuminate\Http\Response
     */
    public function destroy(Servicio $servicio)
    {
        //
    }
}
