<?php

namespace App\Http\Controllers;

use App\Programa;
use App\Servicio;
use App\Http\Requests\Programa\StoreRequest; 
use App\Http\Requests\Programa\UpdateRequest; 
use Illuminate\Http\Request;

class ProgramaController extends Controller
{

    public function index()
    {
        return view('themes.backoffice.pages.programa.index', [
            'programa' => Programa::all(),
        ]);
    }

    public function create()
    {
        $servicios = Servicio::all();
        return view('themes.backoffice.pages.programa.create', compact('servicios'));
    }

    public function store(StoreRequest $request, Programa $programa)
    {
        $programa = $programa->store($request);
        return redirect()->route('backoffice.programa.show', $programa);
    }

    public function show(Programa $programa)
    {
        return view('themes.backoffice.pages.programa.show', [
            'programa' => $programa,
        ]);
    }

    public function edit(Programa $programa)
    {
        $this->authorize('update', $programa);
        return view('themes.backoffice.pages.programa.edit',[
            'programa'=> $programa,
            'servicios' => Servicio::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Programa  $programa
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Programa $programa)
    {
        $programa->my_update($request);
        return redirect()->route('backoffice.programa.show', $programa);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Programa  $programa
     * @return \Illuminate\Http\Response
     */
    public function destroy(Programa $programa)
    {
        //
    }
}
