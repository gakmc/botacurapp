<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AbonoExtraMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $abonoExtra;
    public $reserva;
    public $cliente;
    public $venta;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($abonoExtra, $reserva, $cliente, $venta)
    {
        $this->abonoExtra = $abonoExtra;
        $this->reserva = $reserva;
        $this->cliente = $cliente;
        $this->venta = $venta;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.abono_extra')
                    ->subject('Confirmación de Abono')
                    ->with([
                        'nombre' => $this->cliente->nombre_cliente,
                        'fecha_visita' => Carbon::parse($this->reserva->fecha_visita)->format('d-m-Y'),
                        'programa' => $this->reserva->programa->nombre_programa,
                        'monto' => $this->abonoExtra->monto,
                        'fecha_abono' => Carbon::parse($this->abonoExtra->fecha_abono)->format('d-m-Y'),
                        'tipo_transaccion' => $this->abonoExtra->tipoTransaccion->nombre,
                        'folio' => $this->abonoExtra->folio,
                        'saldo_pendiente' => $this->venta->total_pagar,
                    ]);
    }
}
