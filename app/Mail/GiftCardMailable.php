<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class GiftCardMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $gc;
    public $pdfData;


    public function __construct($gc, $pdfData)
    {
        $this->gc = $gc;
        $this->pdfData = $pdfData;
    }


    public function build()
    {
        return $this->view('emails.giftcard')
                    ->subject('Botacura Entrega de Gift Card')
                    ->attachData($this->pdfData, 'GiftCard-'.$this->gc->para.'-'.$this->gc->id.'.pdf');
    }
}
