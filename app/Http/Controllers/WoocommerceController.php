<?php

namespace App\Http\Controllers;

use App\Cliente;
use App\WoocommerceOrder;
use Illuminate\Http\Request;

class WoocommerceController extends Controller
{
    public function index()
    {
        $ventas = WoocommerceOrder::with('programa')->get();

        $emails = $ventas->pluck('billing_email')->unique()->filter()->values();

        $clientes = Cliente::with('reservas')
            ->whereIn('correo', $emails)
            ->get()
            ->keyBy(function ($c) { return strtolower(trim($c->correo)); });

        // Ocultar órdenes cuyo cliente ya tiene una reserva en la misma fecha de visita
        $ventas = $ventas->filter(function ($venta) use ($clientes) {
            if (!$venta->fecha_visita_wc) {
                return true;
            }

            $emailKey = strtolower(trim($venta->billing_email ?? ''));
            $cliente  = $clientes->get($emailKey);

            if (!$cliente) {
                return true;
            }

            $fechaWc = \Carbon\Carbon::parse($venta->fecha_visita_wc)->format('Y-m-d');

            $tieneReserva = $cliente->reservas->contains(function ($r) use ($fechaWc) {
                if (!$r->fecha_visita) {
                    return false;
                }
                return $r->fecha_visita === $fechaWc;
            });

            return !$tieneReserva;
        });

        return view('themes.backoffice.pages.woocommerce.index', compact('ventas', 'clientes'));
    }

}
