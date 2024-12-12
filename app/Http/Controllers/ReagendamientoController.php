<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Reagendamiento;
use App\Reserva;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class ReagendamientoController extends Controller
{

    public function index()
    {
        //
    }

    public function create($reserva)
    {

        $reserva = Reserva::findOrFail($reserva);

        return view('themes.backoffice.pages.reagendamiento.create', [
            'reserva' => $reserva,
        ]);

    }

    public function store(Request $request, Reserva $reserva)
    {

        $validarData = $request->validate([
            'nueva_fecha' => 'required|date',
        ]);

        // Guardar la fecha original de la reserva en el reagendamiento
        $reagendamiento = Reagendamiento::create([
            'fecha_original' => Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d'),
            'nueva_fecha' => $validarData['nueva_fecha'],
            'id_reserva' => $request->input('id_reserva'),
        ]);

        // Actualizar la reserva con la nueva fecha de visita
        $reserva->fecha_visita = $validarData['nueva_fecha'];
        $reserva->save();

        Alert::success('Éxito', 'Se ha reagendado la visita')->showConfirmButton();
        return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva->id]);

    }

    public function show(Reagendamiento $reagendamiento)
    {
        //
    }

    public function edit(Reagendamiento $reagendamiento)
    {
        //
    }

    public function update(Request $request, Reagendamiento $reagendamiento)
    {
        //
    }

    public function destroy(Reagendamiento $reagendamiento)
    {
        //
    }
}
