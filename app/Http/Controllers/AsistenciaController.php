<?php

namespace App\Http\Controllers;

use App\Asistencia;
use App\Sueldo;
use App\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AsistenciaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $rolesValidos = ['Administracion', 'Mantencion', 'Informatica', 'Aseo'];

        $users = User::whereHas('roles', function ($query) use ($rolesValidos) {
            $query->whereIn('name', $rolesValidos);
        })->get();

        // Todas las asistencias, agrupadas por fecha
         $asignados = Asistencia::with('users.roles')->get()->keyBy('fecha');


        // $fechas = collect(CarbonPeriod::create(now()->subDays(15), now()->addDays(15)))
        // ->map(function ($date) {
        //     return $date->format('Y-m-d');
        // });

        $rango = collect(CarbonPeriod::create(now()->subDays(15), now()->addDays(15)))
            ->map(function ($date) {
                return $date->format('Y-m-d');
            });

        $fechasRegistradas = Asistencia::pluck('fecha')
            ->map(function ($fecha) {
                return \Carbon\Carbon::parse($fecha)->format('Y-m-d');
            });

        $fechas = $rango->merge($fechasRegistradas)->unique()->sort();

        return view('themes.backoffice.pages.asistencia.index', compact('users', 'fechas', 'asignados'));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        
        $params = $request->fecha;
        $fecha = Carbon::parse($params)->format('d-m-Y');
        
        $rolesValidos = ['Administracion', 'Mantencion', 'Informatica', 'Aseo'];

        $users = User::whereHas('roles', function ($query) use ($rolesValidos) {
            $query->whereIn('name', $rolesValidos);
        })->get();

        return view('themes.backoffice.pages.asistencia.create',compact('fecha','users'));
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
        ], [
            'fecha.required' => 'La fecha es requerida',
            'users.required' => 'Debe seleccionar al menos un usuario',
        ]);

        $fecha = Carbon::createFromFormat('d-m-Y', $request->fecha)->format('Y-m-d');

        $asistencia = Asistencia::firstOrCreate([
            'fecha' => $fecha,
        ]);

        $asistencia->users()->sync($request->users);

        foreach ($request->users as $userId) {
            $usuario = User::findOrFail($userId);

            // Evitar duplicidad
            if (!Sueldo::where('id_user', $userId)->where('dia_trabajado', $fecha)->exists()) {

                Sueldo::create([
                    'dia_trabajado' => $fecha,
                    'valor_dia' => $usuario->salario,
                    'sub_sueldo' => $usuario->salario,
                    'total_pagar' => $usuario->salario,
                    'id_user' => $usuario->id,
                ]);
            }
        }

        return redirect()->route('backoffice.asistencia.index')
            ->with('success', 'Se registró la asistencia correctamente.');

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Asistencia  $asistencia
     * @return \Illuminate\Http\Response
     */
    public function show(Asistencia $asistencia)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Asistencia  $asistencia
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $asistencia = Asistencia::findOrFail($id);
        $rolesValidos = ['Administracion', 'Mantencion', 'Informatica', 'Aseo'];

        $users = User::whereHas('roles', function ($query) use ($rolesValidos) {
            $query->whereIn('name', $rolesValidos);
        })->get();

        $fecha = $asistencia->fecha;
        $fecha = Carbon::parse($fecha)->format('d-m-Y');
        
        return view('themes.backoffice.pages.asistencia.edit', compact('asistencia','users', 'fecha' ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Asistencia  $asistencia
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $asistencia = Asistencia::findOrFail($id);
        // dd($request->all(), $asistencia);

        $request->validate([
            'fecha' => 'required|date',
            'users' => 'required|array'
        ]);

        $fecha = Carbon::createFromFormat('d-m-Y', $request->fecha)->format('Y-m-d');

        // Captura antes del sync
        $usuariosAntes = $asistencia->users->pluck('id')->toArray();
        $usuariosDespues = $request->users;

        // Detectar usuarios desmarcados
        $usuariosDesmarcados = array_diff($usuariosAntes, $usuariosDespues);

        // Eliminar sus sueldos
        Sueldo::whereIn('id_user', $usuariosDesmarcados)
            ->where('dia_trabajado', $fecha)
            ->delete();

        // Sincronizar nuevos usuarios
        $asistencia->users()->sync($usuariosDespues);

        // Asegurar que cada usuario nuevo tenga su sueldo (si no existe aún)
        foreach ($usuariosDespues as $userId) {
            $usuario = User::findOrFail($userId);

            Sueldo::updateOrCreate(
                ['id_user' => $userId, 'dia_trabajado' => $fecha],
                [
                    'valor_dia'   => $usuario->salario,
                    'sub_sueldo'  => $usuario->salario,
                    'total_pagar' => $usuario->salario,
                ]
            );
        }

        return redirect()->route('backoffice.asistencia.index')
            ->with('success', 'Asistencia actualizada correctamente.');
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Asistencia  $asistencia
     * @return \Illuminate\Http\Response
     */
    public function destroy(Asistencia $asistencia)
    {
        //
    }
}
