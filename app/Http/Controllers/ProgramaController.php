<?php

namespace App\Http\Controllers;

use App\Programa;
use App\Servicio;
use App\Http\Requests\Programa\StoreRequest; 
use App\Http\Requests\Programa\UpdateRequest; 
use Illuminate\Http\Request;

class ProgramaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('themes.backoffice.pages.programa.index', [
            'programa' => Programa::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $servicios = Servicio::all();
        return view('themes.backoffice.pages.programa.create', compact('servicios'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request, Programa $programa)
    {
        $programa = $programa->store($request);
        return redirect()->route('backoffice.programa.show', $programa);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Programa  $programas
     * @return \Illuminate\Http\Response
     */
    public function show(Programa $programa)
    {
        return view('themes.backoffice.pages.programa.show', [
            'programa' => $programa,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Programa  $programa
     * @return \Illuminate\Http\Response
     */
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
