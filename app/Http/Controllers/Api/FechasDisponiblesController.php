<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\FechaDisponible;

class FechasDisponiblesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $totalUbicaciones = DB::table('ubicaciones')->count();

        // Fechas donde todas las ubicaciones ya están ocupadas (sin cupo para nueva reserva)
        $fechasSinCupo = DB::table('visitas')
            ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
            ->select('reservas.fecha_visita')
            ->whereNotNull('visitas.id_ubicacion')
            ->groupBy('reservas.fecha_visita')
            ->havingRaw('COUNT(DISTINCT visitas.id_ubicacion) >= ?', [$totalUbicaciones])
            ->pluck('reservas.fecha_visita')
            ->map(function ($f) { return \Carbon\Carbon::parse($f)->format('Y-m-d'); })
            ->toArray();

        $fechas = FechaDisponible::where('habilitada', true)
            ->where('fecha', '>=', now()->addDay()->toDateString())
            ->where('fecha', '<=', now()->addDays(120)->toDateString())
            ->whereNotIn('fecha', $fechasSinCupo)
            ->orderBy('fecha')
            ->pluck('fecha')
            ->map(function ($f) { return $f->format('Y-m-d'); })
            ->values();

        return response()->json([
            'success' => true,
            'fechas'  => $fechas,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
