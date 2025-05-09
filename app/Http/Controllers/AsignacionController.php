<?php

namespace App\Http\Controllers;

use App\Asignacion;
use App\Reserva;
use App\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use RealRashid\SweetAlert\Facades\Alert;

class AsignacionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $rolesExcluidos = ['Mantencion', 'Masoterapeuta', 'Administrador']; // IDs de los roles que deseas excluir
        $users = User::whereDoesntHave('roles', function ($query) use ($rolesExcluidos) {
            $query->whereIn('name', $rolesExcluidos);
        })->get();
        $asignados = Asignacion::all()->keyBy('fecha');

        $fechas = Reserva::pluck('fecha_visita')->unique()->map(function ($fecha) {
            return \Carbon\Carbon::createFromFormat('d-m-Y', $fecha)->format('Y-m-d');
        })->toArray();


        return view('themes.backoffice.pages.asignacion.index', compact('users', 'fechas','asignados'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $params = $request->query();
        $fecha = array_key_first($params);
        $fecha = Carbon::parse($fecha)->format('d-m-Y');

        // $rolesExcluidos = ['Mantencion', 'Masoterapeuta', 'Administrador']; // IDs de los roles que deseas excluir
        // $users = User::whereDoesntHave('roles', function ($query) use ($rolesExcluidos) {
        //     $query->whereIn('name', $rolesExcluidos);
        // })->get();

        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['anfitriona', 'barman', 'cocina', 'garzon', 'jefe local']);
        })->get();

        return view('themes.backoffice.pages.asignacion.create', ['fecha'=>$fecha, 'users'=>$users]);
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
            'fecha' => 'required',
            'users' => 'required|array'
        ],[
            'fecha.required' => 'La fecha es requerida',
            'users.required' => 'Debe seleccionar al menos un usuario',
        ]);

        $asignacion = Asignacion::create([
            'fecha' => Carbon::createFromFormat('d-m-Y', $request->fecha)->format('Y-m-d'),
        ]);

        $asignacion->users()->sync($request->users);

        Alert::success('Éxito', 'Equipo asignado correctamente', 'Confirmar')->showConfirmButton();
        return redirect()->route('backoffice.asignacion.index');
    }

    public function show(asignacion $asignacion)
    {
        //
    }

    public function edit(asignacion $asignacion)
    {
        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['anfitriona', 'barman', 'cocina', 'garzon', 'jefe local']);
        })->get();

        $fecha = $asignacion->fecha;
        $fecha = Carbon::parse($fecha)->format('d-m-Y');
        
        return view('themes.backoffice.pages.asignacion.edit', compact('asignacion','users', 'fecha' ));
    }

    public function update(Request $request, asignacion $asignacion)
    {
        $request->validate([
            'fecha' => 'required',
            'users' => 'required|array'
        ],[
            'fecha.required' => 'La fecha es requerida',
            'users.required' => 'Debe seleccionar al menos un usuario',
        ]);

        $asignacion->users()->sync($request->users);
        Alert::success('Éxito', 'Equipo editado correctamente para el '.$request->fecha, 'Confirmar')->showConfirmButton();
        return redirect()->route('backoffice.asignacion.index');

    }

    public function destroy(asignacion $asignacion)
    {
        //
    }
}
