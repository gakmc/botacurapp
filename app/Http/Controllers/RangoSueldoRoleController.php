<?php

namespace App\Http\Controllers;

use App\RangoSueldoRole;
use App\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RangoSueldoRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $rangos = RangoSueldoRole::with('role')->get()->each(function ($rangoSueldoRole) {
        //     $rangoSueldoRole->role_name = $rangoSueldoRole->role->name;
        // });

        // return view('themes.backoffice.pages.sueldo.por-role.index', compact('rangos'));

        $filtro = $request->input('filtro', 'vigentes');

        $query = RangoSueldoRole::with('role');

        if ($filtro === 'vigentes') {
            $query->whereNull('vigente_hasta');
        } elseif ($filtro === 'no-vigentes') {
            $query->whereNotNull('vigente_hasta');
        }

        $rangos = $query->orderByDesc('id')->get();

        switch ($filtro) {
            case 'vigentes':
                $titulo = '(Vigentes)';
                break;
            case 'no-vigentes':
                $titulo = '(No Vigentes)';
                break;
            default:
                $titulo = '(Todos)';
                break;
        };


        return view('themes.backoffice.pages.sueldo.por-role.index', compact('rangos', 'filtro', 'titulo'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $rolesConRangoVigente = RangoSueldoRole::whereNull('vigente_hasta')
            ->pluck('role_id')
            ->toArray();

        // $roles = Role::all();
        $roles = Role::WhereNotIn('id', $rolesConRangoVigente)->get();

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
            'role_id' => [
            'required',
            'exists:roles,id',
                Rule::unique('rango_sueldo_roles')->where(function ($query) {
                    return $query->whereNull('vigente_hasta');
                }),
            ],
            'sueldo_base' => 'required|numeric|min:0',
        ]);

        RangoSueldoRole::create([
            'role_id' => $request->role_id,
            'sueldo_base' => $request->sueldo_base,
            'vigente_desde' => Carbon::now(),
            'vigente_hasta' => null,
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
        $rango = RangoSueldoRole::findOrFail($id);
        $roles = Role::all();
        // dd($rango);
        return view('themes.backoffice.pages.sueldo.por-role.create', compact('rango', 'roles'));
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
        $rango = RangoSueldoRole::findOrFail($id);
        $request->merge([
            'sueldo_base' => (int) str_replace(['$','.',','],'',$request->sueldo_base),
        ]);

        $request->validate([
            // 'role_id' => 'required|exists:roles,id',
            'sueldo_base' => 'required|numeric|min:0',
            // 'vigente_desde' => 'required|date',
            // 'vigente_hasta' => 'nullable|date|after_or_equal:vigente_desde',
        ]);

        if ((int)$rango->sueldo_base !== (int)$request->sueldo_base) {

            $rango->vigente_hasta = Carbon::now();
            $rango->save();

            RangoSueldoRole::create([
                'role_id' => $rango->role_id,
                'sueldo_base' => $request->sueldo_base,
                'vigente_desde' => Carbon::now(),
                'vigente_hasta' => null,
            ]);

            return redirect()->route('backoffice.rango-sueldos.index')
                ->with('success', 'Rango de sueldo actualizado exitosamente.');
        }else{
            return redirect()->route('backoffice.rango-sueldos.index')
                ->with('info', 'No fue requerido actualizar la informacion.');
        }
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
