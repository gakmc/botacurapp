<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Cotizacion;

class CotizacionMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    
    // public $cotizacion;
    // private $pdfData;
    
    // public function __construct(Cotizacion $cotizacion, $pdfData)
    // {
    //     $this->cotizacion = $cotizacion;
    //     $this->pdfData    = $pdfData;
    // }

    // public function build()
    // {
        
    //     $email = $this->subject('Cotizacion N.° '.$this->cotizacion->id)
    //     ->view('emails.cotizacion')
    //     ->with('data', $this->cotizacion)
    //     ->attachData(
    //         $this->pdfData,
    //         'cotizacion_'.$this->cotizacion->id.'.pdf',
    //         ['mime' => 'application/pdf']
    //     );
        
    //     // Adjuntar imagen en el programa si existe
    //     $item = $this->cotizacion->items->first();
    //     $programa = $item ? $item->itemable : null;


    //     if ($programa && $programa->slug) {
    //         $slug = $programa->slug;

    //         // Estensiones Posibles
    //         $extensiones = ['jpg','jpeg','png','webp'];

    //         foreach ($extensiones as $ext) {
    //             $url = "https://botacura.cl/wp-content/uploads/2025/programas/{$slug}.{$ext}";

    //             // Verificar si el archivo existe
    //             $headers = @get_headers($url);
    //             if ($headers && strpos($headers[0], '200') !== false) {
    //                $imageContent = file_get_contents($url);

    //                // Definir mime
    //                $mime = 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);

    //                $email->attachData(
    //                 $imageContent,
    //                 "Programa.{$ext}",
    //                 ['mime' => $mime]
    //                );

    //                // Solo adjuntar la primera coincidencia
    //                break;
    //             }
    //         }
    //     }

    //     return $email;
    // }




    public $cotizacion;

    public function __construct(Cotizacion $cotizacion)
    {
        $this->cotizacion = $cotizacion;
    }

    public function build()
    {
        // Preparar datos para el PDF
        $emitida = $this->cotizacion->fecha_emision->isoFormat('D [de] MMMM');
        $reserva = $this->cotizacion->fecha_reserva->isoFormat('D [de] MMMM');

        $pdfData = Pdf::loadView(
            'pdf.cotizacion.viewPDF',
            [
                'cotizacion' => $this->cotizacion,
                'emitida'    => $emitida,
                'reserva'    => $reserva,
            ]
        )->output();

        $email = $this->subject('Cotizacion N.° '.$this->cotizacion->id)
                      ->view('emails.cotizacion')
                      ->with('data', $this->cotizacion)
                      ->attachData(
                          $pdfData,
                          'cotizacion_'.$this->cotizacion->id.'.pdf',
                          ['mime' => 'application/pdf']
                      );

        // Adjuntar imagen del programa (como ya tenías)
        $item     = $this->cotizacion->items->first();
        $programa = $item ? $item->itemable : null;

        if ($programa && $programa->slug) {
            $slug        = $programa->slug;
            $extensiones = ['jpg', 'jpeg', 'png', 'webp'];

            foreach ($extensiones as $ext) {
                $url = "https://botacura.cl/wp-content/uploads/2025/programas/{$slug}.{$ext}";
                $headers = @get_headers($url);

                if ($headers && strpos($headers[0], '200') !== false) {
                    $imageContent = file_get_contents($url);
                    $mime = 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);

                    $email->attachData(
                        $imageContent,
                        "Programa.{$ext}",
                        ['mime' => $mime]
                    );

                    break;
                }
            }
        }

        return $email;
    }



}
