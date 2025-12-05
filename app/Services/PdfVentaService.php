<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PdfVentaService
{
    /**
     * Genera PDF, lo guarda temporalmente, prepara data y envía correo
     *
     * @param string $view          Vista Blade del PDF
     * @param array  $data          Data completa usada en PDF + correo
     * @param string $baseFilename  Prefijo del PDF
     * @param string $nombreCliente Nombre del cliente
     * @param mixed  $fechaVisita   Fecha (Carbon|string)
     * @param string $mailableClass Clase del Mailable
     */
    public function generarYEnviarPDF(
        string $view,
        array  $data,
        string $baseFilename,
        string $nombreCliente,
        $fechaVisita,
        string $mailableClass
    ): void
    {
        $inicioDebug = microtime(true);
            
    
        // 1) Nombre del archivo
        $fecha = $fechaVisita instanceof Carbon
        ? $fechaVisita->format('Ymd')
        : Carbon::parse($fechaVisita)->format('Ymd');
        
        $slug = Str::slug($nombreCliente, '_');
        
        $fileName = "{$baseFilename}_{$slug}_{$fecha}.pdf";
        
        // Carpeta temp
        $dir = storage_path('app/temp_pdfs');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        
        $fullPath = "{$dir}/{$fileName}";
        
        try {
            
            // 2) Generar PDF
            $pdf = Pdf::loadView($view, $data);
            $pdf->save($fullPath);
            
            // 3) Agregar ruta del PDF a la data (así lo requieren los mailables)
            $data['pdfPath'] = $fullPath;
            
            // 4) Crear mailable
            $mailable = new $mailableClass($data);
            
            // 5) Enviar
            try {
                if (!empty($data['correo'])) {
                    Mail::to($data['correo'])->send($mailable);
                }
            } catch (\Throwable $e) {
                Log::error('Error al enviar correo PDF: '.$e->getMessage(), [
                    'correo' => $data['correo'] ?? null,
                    'file'   => $fullPath,
                ]);
            }
            
        } finally {
            
            // 6) Borrar archivo temporal
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
        }
        
        $finDebug = microtime(true);
        Log::info("Tiempo generar PDF Cierre de ventas (PdfVentaService.php): ".round($finDebug - $inicioDebug, 3)." s");
    }
}
