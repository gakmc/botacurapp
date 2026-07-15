<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\FechaDisponible;
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

        $habilitadas = FechaDisponible::where('habilitada', true)
            ->where('fecha', '>=', today())
            ->pluck('fecha')
            ->map(function ($f) { return $f->format('Y-m-d'); })
            ->toArray();

        // Calcular qué fechas del rango deshabilitar en pickadate
        // Formato requerido por pickadate: [year, month(0-indexed), day]
        $fechasDeshabilitadas = [];
        $cursor  = today()->addDay();
        $maxDate = today()->addDays(120);

        while ($cursor <= $maxDate) {
            if (!in_array($cursor->format('Y-m-d'), $habilitadas)) {
                $fechasDeshabilitadas[] = [(int) $cursor->year, (int) $cursor->month - 1, (int) $cursor->day];
            }
            $cursor->addDay();
        }

        return view('themes.backoffice.pages.reagendamiento.create', [
            'reserva'              => $reserva,
            'fechasDeshabilitadas' => $fechasDeshabilitadas,
        ]);

    }

    public function store(Request $request, Reserva $reserva)
    {

        $validarData = $request->validate([
            'nueva_fecha' => 'required|date',
        ]);

        $nuevaFecha = Carbon::createFromFormat('d-m-Y', $validarData['nueva_fecha'])->format('Y-m-d');

        // Guardar la fecha original de la reserva en el reagendamiento
        $reagendamiento = Reagendamiento::create([
            'fecha_original' => Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d'),
            'nueva_fecha' => $nuevaFecha,
            'id_reserva' => $request->input('id_reserva'),
        ]);

        // Actualizar la reserva con la nueva fecha de visita
        $reserva->fecha_visita = $nuevaFecha;
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
