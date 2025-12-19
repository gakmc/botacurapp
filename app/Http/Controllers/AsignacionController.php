<?php

namespace App\Http\Controllers;

use App\Asignacion;
use App\Reserva;
use App\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        // $asignados = Asignacion::all()->keyBy('fecha');

        // $fechas = Reserva::pluck('fecha_visita')->unique()->map(function ($fecha) {
        //     return \Carbon\Carbon::createFromFormat('d-m-Y', $fecha)->format('Y-m-d');
        // })->toArray();


        // return view('themes.backoffice.pages.asignacion.index', compact('users', 'fechas','asignados'));


        return view('themes.backoffice.pages.asignacion.index', compact('users'));


       
    }




    public function eventosCalendar(Request $request)
    {
       
        // 1) Parsear rango que manda FullCalendar
            $start = Carbon::parse($request->query('start'))->startOfDay();
            $end   = Carbon::parse($request->query('end'))->endOfDay();


            // 2) Traer todas las reservas (solo lo necesario)
            $reservasRaw = Reserva::select('id', 'fecha_visita')->get();

            // 3) Filtrar por rango EN PHP, convirtiendo d-m-Y → Carbon
            $reservasFiltradas = $reservasRaw->filter(function ($r) use ($start, $end) {
                try {
                    $fecha = Carbon::createFromFormat('d-m-Y', $r->fecha_visita);
                } catch (\Exception $e) {
                    return false; // por si hay alguna fecha mal formateada
                }

                return $fecha->betweenIncluded($start, $end);
            });

            // 4) Agrupar por día normalizado (Y-m-d)
            $reservas = $reservasFiltradas->groupBy(function ($r) {
                return Carbon::createFromFormat('d-m-Y', $r->fecha_visita)->toDateString();
            });

            if ($reservas->isEmpty()) {

                return response()->json([]);
            }

            $fechasISO = $reservas->keys();

            // 5) Asignaciones para esas fechas (Asignacion.fecha está en Y-m-d)
            $asignaciones = Asignacion::with(['users.roles'])
                ->whereIn('fecha', $fechasISO)
                ->get()
                ->keyBy('fecha');

            $eventos = [];

            foreach ($fechasISO as $fechaISO) {

                $listaReservas    = $reservas[$fechaISO];
                $cantidadReservas = $listaReservas->count();
                $asignacion       = $asignaciones->get($fechaISO);

                if ($asignacion) {
                    // Hay equipo asignado
                    $usuariosRoles = $asignacion->users->map(function ($user) {
                        $roles = $user->roles->pluck('name')->implode(', ');
                        return $roles
                            ? "{$user->name} ({$roles})"
                            : $user->name;
                    })->values()->all();

                    $eventos[] = [
                        'title' => 'Equipo asignado - ' . implode('; ', $usuariosRoles),
                        'start' => $fechaISO,
                        'color' => 'primary',
                        'extendedProps' => [
                            'asignado'         => true,
                            'usuarios'         => $usuariosRoles,
                            'editUrl'          => route('backoffice.asignacion.edit', $asignacion),
                            'cantidadReservas' => $cantidadReservas,
                        ],
                    ];
                } else {
                    // Día con reservas pero sin asignación
                    $texto = $cantidadReservas === 1 ? ' reserva' : ' reservas';

                    $eventos[] = [
                        'title' => "Sin equipo ({$cantidadReservas}{$texto})",
                        'start' => $fechaISO,
                        'color' => 'red',
                        'extendedProps' => [
                            'asignado'         => false,
                            'createUrl'        => route('backoffice.asignacion.create', $fechaISO),
                            'cantidadReservas' => $cantidadReservas,
                        ],
                    ];
                }
            }


            return response()->json($eventos);
 
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
