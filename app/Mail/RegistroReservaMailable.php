<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistroReservaMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $visita;
    public $reserva;
    public $cliente;
    public $programa;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($visita,$reserva, $cliente, $programa)
    {
        $this->visita = $visita;
        $this->reserva = $reserva;
        $this->cliente = $cliente;
        $this->programa = $programa;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.reserva')
                    ->subject('Notificación de Reserva')
                    ->with([
                        'nombre'=>$this->cliente->nombre_cliente,
                        'cantidad_personas'=>$this->reserva->cantidad_personas,
                        'fecha_visita'=>$this->reserva->fecha_visita,
                        'programa' => $this->programa->nombre_programa,
                    ]);
    }
}
