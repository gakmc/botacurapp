<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Cotizacion;

class CotizacionMailable extends Mailable
{
    use Queueable, SerializesModels;

    
    public $cotizacion;
    private $pdfData;
    
    public function __construct(Cotizacion $cotizacion, $pdfData)
    {
        $this->cotizacion = $cotizacion;
        $this->pdfData    = $pdfData;
    }

    public function build()
    {
        return $this->subject('Cotizacion N.° '.$this->cotizacion->id)
                    ->view('emails.cotizacion')
                    ->with('data', $this->cotizacion)
                    ->attachData(
                        $this->pdfData,
                        'cotizacion_'.$this->cotizacion->id.'.pdf',
                        ['mime' => 'application/pdf']
                    );
    }
}
