<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reserva;
use App\Cliente;
use App\AbonoExtra;
use App\Mail\RegistroReservaMailable;
use App\Mail\AbonoExtraMailable;
use App\Mail\AbonoExtraEliminadoMailable;
use Illuminate\Support\Facades\Mail;

class EmailPreviewController extends Controller
{
    public function preview()
    {
        // Simular una visita y una reserva para previsualización
        $reserva = Reserva::first(); // Usa un ejemplo de Reserva de tu base de datos
        if (!$reserva) {
            return "No hay reservas disponibles para previsualizar.";
        }

        $visita = $reserva->visitas; // Usa un ejemplo de Visita de tu base de datos
        $cliente = Cliente::first(); // Usa un ejemplo de Cliente de tu base de datos
        $programa = $reserva->programa;

        // Devolver la vista del correo
        return new RegistroReservaMailable($visita, $reserva, $cliente, $programa);
    }

    public function previewAbonoExtra()
    {
        $abonoExtra = AbonoExtra::with('tipoTransaccion', 'venta.reserva.cliente', 'venta.reserva.programa')->first();
        if (!$abonoExtra) {
            return "No hay abonos extra disponibles para previsualizar.";
        }

        $venta = $abonoExtra->venta;
        $reserva = $venta->reserva;
        $cliente = $reserva->cliente;

        return new AbonoExtraMailable($abonoExtra, $reserva, $cliente, $venta);
    }

    public function previewAbonoExtraEliminado()
    {
        $abonoExtra = AbonoExtra::with('tipoTransaccion', 'venta.reserva.cliente', 'venta.reserva.programa')->first();
        if (!$abonoExtra) {
            return "No hay abonos extra disponibles para previsualizar.";
        }

        $venta = $abonoExtra->venta;
        $reserva = $venta->reserva;
        $cliente = $reserva->cliente;

        $datosAbono = [
            'monto'            => $abonoExtra->monto,
            'fecha_abono'      => $abonoExtra->fecha_abono,
            'tipo_transaccion' => $abonoExtra->tipoTransaccion->nombre,
            'folio'            => $abonoExtra->folio,
        ];

        return new AbonoExtraEliminadoMailable($datosAbono, $reserva, $cliente, $venta);
    }
}
