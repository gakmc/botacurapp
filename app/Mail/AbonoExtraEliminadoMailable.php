<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AbonoExtraEliminadoMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $datosAbono;
    public $reserva;
    public $cliente;
    public $venta;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($datosAbono, $reserva, $cliente, $venta)
    {
        $this->datosAbono = $datosAbono;
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
        return $this->view('emails.abono_extra_eliminado')
                    ->subject('Corrección de Abono - Fe de Erratas')
                    ->with([
                        'nombre' => $this->cliente->nombre_cliente,
                        'fecha_visita' => Carbon::parse($this->reserva->fecha_visita)->format('d-m-Y'),
                        'programa' => $this->reserva->programa->nombre_programa,
                        'monto' => $this->datosAbono['monto'],
                        'fecha_abono' => Carbon::parse($this->datosAbono['fecha_abono'])->format('d-m-Y'),
                        'tipo_transaccion' => $this->datosAbono['tipo_transaccion'],
                        'folio' => $this->datosAbono['folio'],
                        'saldo_pendiente' => $this->venta->total_pagar,
                    ]);
    }
}
