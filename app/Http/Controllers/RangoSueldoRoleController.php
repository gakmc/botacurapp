<?php

namespace App\Http\Controllers;

use App\RangoSueldoRole;
use App\Role;
use Illuminate\Http\Request;

class RangoSueldoRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $rangos = RangoSueldoRole::with('role')->get()->each(function ($rangoSueldoRole) {
            $rangoSueldoRole->role_name = $rangoSueldoRole->role->name;
        });

        return view('themes.backoffice.pages.sueldo.por-role.index', compact('rangos'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::all();
        return view('themes.backoffice.pages.sueldo.por-role.create', compact('roles'));
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
            'sueldo_base'    => (int) str_replace(['$', '.', ','], '', $request->sueldo_base),
        ]);

        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'sueldo_base' => 'required|numeric|min:0',
            'vigente_desde' => 'required|date',
            'vigente_hasta' => 'nullable|date|after_or_equal:vigente_desde',
        ]);

        dd($request->all());
        RangoSueldoRole::create([
            'role_id' => $request->role_id,
            'sueldo_base' => $request->sueldo_base,
            'vigente_desde' => $request->vigente_desde,
            'vigente_hasta' => $request->vigente_hasta,
        ]);
        return redirect()->route('backoffice.rango-sueldos.index')
            ->with('success', 'Rango de sueldo creado exitosamente.');
    
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
        //
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
        //
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
