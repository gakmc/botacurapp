<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reserva;
use App\Cliente;
use App\Mail\RegistroReservaMailable;
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
}
