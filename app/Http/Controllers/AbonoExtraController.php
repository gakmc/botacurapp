<?php
namespace App\Http\Controllers;

use App\AbonoExtra;
use App\Reserva;
use App\TipoTransaccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AbonoExtraController extends Controller
{
    public function index(Reserva $reserva)
    {
        $reserva->load('cliente', 'venta.abonosExtra.tipoTransaccion', 'venta.abonosExtra.user');
        $tipos = TipoTransaccion::all();

        return view('themes.backoffice.pages.reserva.abonos.index', [
            'reserva' => $reserva,
            'venta'   => $reserva->venta,
            'tipos'   => $tipos,
        ]);
    }

    public function store(Request $request, Reserva $reserva)
    {
        $venta = $reserva->venta;

        $request->validate([
            'monto'               => 'required|integer|min:1|max:' . (int) $venta->total_pagar,
            'fecha_abono'         => 'required|date_format:Y-m-d',
            'id_tipo_transaccion' => 'required|exists:tipos_transacciones,id',
            'folio'               => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $venta) {
            AbonoExtra::create([
                'id_venta'             => $venta->id,
                'monto'                => $request->monto,
                'fecha_abono'          => $request->fecha_abono,
                'id_tipo_transaccion'  => $request->id_tipo_transaccion,
                'folio'                => $request->folio,
                'user_id'              => auth()->id(),
            ]);

            $venta->decrement('total_pagar', $request->monto);
        });

        return redirect()->route('backoffice.reserva.abonos.index', $reserva)->with('success', 'Abono registrado exitosamente.');
    }

    public function destroy(AbonoExtra $abonoExtra)
    {
        $reserva = $abonoExtra->venta->reserva;

        DB::transaction(function () use ($abonoExtra) {
            $abonoExtra->venta->increment('total_pagar', $abonoExtra->monto);
            $abonoExtra->delete();
        });

        return redirect()->route('backoffice.reserva.abonos.index', $reserva)->with('success', 'Abono eliminado exitosamente.');
    }
}
