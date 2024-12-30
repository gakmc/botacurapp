<?php

namespace App\Http\Controllers;

use App\Masaje;
use App\Reserva;
use App\Visita;
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
    public function index()
    {
        // Asignación de la fecha actual
        $fechaActual = Carbon::now()->startOfDay();
    
        // Obtener todas las reservas con visitas cuya fecha de visita es hoy o posterior
        $reservas = Reserva::where('fecha_visita', '>=', $fechaActual)
        ->join('clientes as c', 'reservas.cliente_id', '=', 'c.id')
        ->join('visitas as v', 'v.id_reserva', '=', 'reservas.id')
        ->join('lugares_masajes as lm', 'lm.id', '=', 'v.id_lugar_masaje')
        ->select('reservas.*', 'v.*', 'v.horario_sauna', 'v.horario_tinaja', 'v.horario_masaje', 'c.nombre_cliente', 'lm.nombre as lugarMasaje')
        ->orderBy('reservas.fecha_visita', 'asc')
        ->orderBy('v.horario_masaje', 'asc')
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
    
        // Retorno de la vista
        return view('themes.backoffice.pages.masaje.index', [
            'reservasPaginadas' => $reservasPaginadas,
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
                // Validar la solicitud
                $request->validate([
                    'id_visita' => 'required|exists:visitas,id',
                    'persona_numero' => 'required|integer|min:1',
                ]);
        
                $masaje = Masaje::create([
                    'persona' => $request->persona_numero,
                    'id_visita' => $request->id_visita,
                    'user_id' => auth()->id(),
                ]);
        
        
                // Redirigir con un mensaje de éxito
                Alert::toast('Masaje asignado correctamente', 'success');
                return redirect()->back()->with('success', 'Masaje asignado correctamente');
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
