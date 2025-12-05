<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConsumoMailable extends Mailable
{
    use Queueable, SerializesModels;


    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }


    public function build()
    {
        $inicioDebug = microtime(true);
                
        
        $finDebug = microtime(true);
        Log::info("Tiempo para generar email Consumo (ConsumoMailable): ".round($finDebug - $inicioDebug, 3)." s");

        return $this->view('emails.consumo')
                ->subject('Detalle de su Consumo')
                ->with('data', $this->data)
                ->attach($this->data['pdfPath'],[
                    'as' => 'Detalle_Consumo.pdf',
                    'mime' => 'application/pdf'
                ]);
    }
}
