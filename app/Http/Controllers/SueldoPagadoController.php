<?php

namespace App\Http\Controllers;

use App\SueldoPagado;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SueldoPagadoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'sueldos_seleccionados' => 'required|array|min:1',
            'motivo' => 'nullable|string|max:255',
            'bono'   => 'nullable|string',
        ]);

        $bono = (int) str_replace(['$', '.', ','],'', $request->bono ?? 0);

        // dd($request->all());


        foreach ($request->sueldos_seleccionados as $item){
            $data = json_decode($item, true);
            SueldoPagado::create([
                'user_id' => $data['user_id'],
                'semana_inicio' => $data['inicio'],
                'semana_fin' => $data['fin'],
                'fecha_pago' => Carbon::now()->format('Y-m-d'),
                'monto' => $data['total'],
                'bono' => $bono,
                'motivo' => $request->motivo ?? null,
            ]);
        }

        return back()->with('success','Pagos registrados exitosamente.');
    }
}
