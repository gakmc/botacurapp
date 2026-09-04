<?php
namespace App\Http\Controllers;

use App\AbonoExtra;
use App\Mail\AbonoExtraEliminadoMailable;
use App\Mail\AbonoExtraMailable;
use App\Reserva;
use App\TipoTransaccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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

        $abonoExtra = DB::transaction(function () use ($request, $venta) {
            $abonoExtra = AbonoExtra::create([
                'id_venta'             => $venta->id,
                'monto'                => $request->monto,
                'fecha_abono'          => $request->fecha_abono,
                'id_tipo_transaccion'  => $request->id_tipo_transaccion,
                'folio'                => $request->folio,
                'user_id'              => auth()->id(),
            ]);

            $venta->decrement('total_pagar', $request->monto);

            return $abonoExtra;
        });

        $cliente = $reserva->cliente;

        if ($cliente && $cliente->correo) {
            $abonoExtra->load('tipoTransaccion');
            Mail::to($cliente->correo)->send(new AbonoExtraMailable($abonoExtra, $reserva, $cliente, $venta->fresh()));
        }

        return redirect()->route('backoffice.reserva.show', $reserva)->with('success', 'Abono registrado exitosamente.');
    }

    public function destroy(AbonoExtra $abonoExtra)
    {
        $abonoExtra->load('tipoTransaccion', 'venta.reserva.cliente');

        $venta = $abonoExtra->venta;
        $reserva = $venta->reserva;
        $cliente = $reserva->cliente;

        $datosAbono = [
            'monto'            => $abonoExtra->monto,
            'fecha_abono'      => $abonoExtra->fecha_abono,
            'tipo_transaccion' => $abonoExtra->tipoTransaccion->nombre,
            'folio'            => $abonoExtra->folio,
        ];

        DB::transaction(function () use ($abonoExtra) {
            $abonoExtra->venta->increment('total_pagar', $abonoExtra->monto);
            $abonoExtra->delete();
        });

        if ($cliente && $cliente->correo) {
            Mail::to($cliente->correo)->send(new AbonoExtraEliminadoMailable($datosAbono, $reserva, $cliente, $venta->fresh()));
        }

        return redirect()->route('backoffice.reserva.abonos.index', $reserva)->with('success', 'Abono eliminado exitosamente.');
    }
}
