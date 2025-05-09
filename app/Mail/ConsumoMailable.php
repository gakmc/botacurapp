<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConsumoMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;


    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }


    public function build()
    {
        return $this->view('emails.consumo')
                ->subject('Detalle de su Consumo')
                ->with('data', $this->data)
                ->attach($this->data['pdfPath'],[
                    'as' => 'Detalle_Consumo.pdf',
                    'mime' => 'application/pdf'
                ]);
    }
}
