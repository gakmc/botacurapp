<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VentaCerradaMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.venta_cerrada')
            ->subject('Detalle de su Venta')
            ->with('data', $this->data)
            ->attach($this->data['pdfPath'],[
                'as' => 'Detalle_Venta.pdf',
                'mime' => 'application/pdf'
            ]);
    }
}
