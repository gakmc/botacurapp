<?php

namespace App\Http\Controllers;

use App\Masaje;
use App\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use RealRashid\SweetAlert\Facades\Alert;

class MasajeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function indexlod()
    {
        $masajes = Masaje::with(['visita.reserva'])->get();
        dd($masajes);
    }

    public function index()
    {
        // Asignación de la fecha actual
        $fechaActual = Carbon::now()->startOfDay();

        // Obtener todas las reservas con visitas cuya fecha de visita es hoy o posterior
        // $reservas = Reserva::where('fecha_visita', '>=', $fechaActual)
        //     ->join('clientes as c', 'reservas.cliente_id', '=', 'c.id')
        //     ->join('visitas as v', 'v.id_reserva', '=', 'reservas.id')
        //     ->join('masajes as m', 'm.id_visita', '=', 'v.id')
        //     ->join('lugares_masajes as lm', 'lm.id', '=', 'm.id_lugar_masaje')
        //     ->select('reservas.*', 'v.*', 'v.horario_sauna', 'v.horario_tinaja', 'm.horario_masaje', 'c.nombre_cliente', 'lm.nombre as lugarMasaje')
        //     ->orderBy('reservas.fecha_visita', 'asc')
        //     ->orderBy('m.horario_masaje', 'asc')
        //     ->get();

        $reservas = Reserva::with(['masajes.lugarMasaje', 'cliente'])
            ->where('fecha_visita', '>=', $fechaActual)
            ->orderBy('fecha_visita', 'asc')
            ->get();

        // Agrupar reservas por fecha
        $reservasPorDia = $reservas->groupBy(function ($reserva) {
            return Carbon::parse($reserva->fecha_visita)->format('d-m-Y');
        });

        // Paginación manual por días
        $perPage = 1; // Número de días por página
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $reservasPorDia->slice(($currentPage - 1) * $perPage, $perPage)->all();

        // Crear el paginador manualmente
        $reservasPaginadas = new LengthAwarePaginator($currentItems, $reservasPorDia->count(), $perPage, $currentPage, [
            'path' => request()->url(),
        ]);

        $distribucionHorarios = [];

        foreach ($reservasPaginadas as $fecha => $reservaciones) {
            foreach ($reservaciones as $reserva) {
                $cantidadPersonas = $reserva->cantidad_personas;
                $horarioMasaje = $reserva->horario_masaje;
                $distribucion = [];
                $indexHorario = 0;

                // Distribuir las personas en los horarios de masajes
                while ($cantidadPersonas > 0) {
                    // Asigna hasta 2 personas por cada horario
                    $personasEnEsteHorario = min($cantidadPersonas, 2);

                    // Calcula el horario ajustando por bloques de 30 minutos
                    $nuevoHorario = date('H:i', strtotime($horarioMasaje));

                    // Agrega la distribución de personas al horario
                    $distribucion[] = [
                        'horario' => $nuevoHorario,
                        'personas' => $personasEnEsteHorario,
                    ];

                    // Resta las personas asignadas y aumenta el índice del horario
                    $cantidadPersonas -= $personasEnEsteHorario;
                    $indexHorario++;
                }

                $distribucionHorarios[$reserva->id] = $distribucion;
            }
        }

        // Retorno de la vista
        return view('themes.backoffice.pages.masaje.index', [
            'reservasPaginadas' => $reservasPaginadas,
            'distribucionHorarios' => $distribucionHorarios,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // // Validar la solicitud
        // $request->validate([
        //     'id_visita' => 'required|exists:visitas,id',
        //     'persona_numero' => 'required|integer|min:1',
        // ]);

        // $masaje = Masaje::create([
        //     'persona' => $request->persona_numero,
        //     'id_visita' => $request->id_visita,
        //     'user_id' => auth()->id(),
        // ]);

        // // Redirigir con un mensaje de éxito
        // Alert::toast('Masaje asignado correctamente', 'success');
        // return redirect()->back()->with('success', 'Masaje asignado correctamente');
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
        $masaje = Masaje::find($id);

        if (!$masaje) {
            return redirect()->back()->with('error', 'Masaje no encontrado');
        }

        $masaje->update([
            'user_id'=>auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Masaje asignado correctamente');
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
