<?php

namespace App\Jobs;

use App\Reserva;
use App\Venta;
use App\PagoConsumo;
use App\Mail\VentaCerradaMailable;
use App\Mail\ConsumoMailable;
use App\Services\PdfVentaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcesarVentaCerrada implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payload;

    /**
     * Recibe un arreglo con IDs y valores numéricos.
    */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
    
    /**
     * Ejecuta el proceso pesado (PDF + correos)
    */
    public function handle()
    {
        $inicioDebug = microtime(true);
        // 1. Recuperar los modelos necesarios (liviano)
        $reserva = Reserva::with(['cliente', 'programa', 'menus'])
            ->findOrFail($this->payload['reserva_id']);

        $venta = Venta::findOrFail($this->payload['venta_id']);

        $pagoConsumo = PagoConsumo::find($this->payload['pago_consumo_id']);

        $consumo = $venta->consumo; // relación

        // 2. Reconstruir el arreglo principal de datos (igual al de tu PDF actual)
        $data = [
            'nombre'        => $reserva->cliente->nombre_cliente,
            'numero'        => $reserva->cliente->whatsapp_cliente,
            'observacion'   => $reserva->observacion ?? 'Sin Observaciones',
            'fecha_visita'  => $reserva->fecha_visita,
            'programa'      => $reserva->programa->nombre_programa,
            'personas'      => $reserva->cantidad_personas,
            'menus'         => $reserva->menus,
            'consumo'       => $consumo,
            'pagoConsumo'   => $pagoConsumo,
            'venta'         => $venta,
            'total'         => $this->payload['total'],
            'propina'       => $this->payload['propina'],
            'propinaPagada' => $this->payload['propinaPagada'],
            'diferencia'    => $this->payload['diferencia'],
            'correo'        => $reserva->cliente->correo,
        ];

        // 3. Generar PDF y enviar correo de venta cerrada
        app(PdfVentaService::class)->generarYEnviarPDF(
            'pdf.venta.viewPDF',
            $data,
            'Detalle_Venta',
            $reserva->cliente->nombre_cliente,
            $reserva->fecha_visita,
            VentaCerradaMailable::class
        );

        // 4. Si se debe enviar el PDF del consumo separado
        if (!empty($this->payload['enviarConsumo']) && $consumo) {

            $dataConsumo = [
                'nombre'        => $reserva->cliente->nombre_cliente,
                'numero'        => $reserva->cliente->whatsapp_cliente,
                'observacion'   => $reserva->observacion ?? 'Sin Observaciones',
                'fecha_visita'  => $reserva->fecha_visita,
                'programa'      => $reserva->programa->nombre_programa,
                'personas'      => $reserva->cantidad_personas,
                'consumo'       => $consumo,
                'pagoConsumo'   => $pagoConsumo,
                'venta'         => $venta,
                'total'         => $this->payload['total'],
                'propina'       => $this->payload['propina'],
                'propinaPagada' => $this->payload['propinaPagada'],
                'correo'        => $reserva->cliente->correo,
            ];

            app(PdfVentaService::class)->generarYEnviarPDF(
                'pdf.consumo_separado.viewPDF',
                $dataConsumo,
                'Detalle_Consumo',
                $reserva->cliente->nombre_cliente,
                $reserva->fecha_visita,
                ConsumoMailable::class
            );
        }

            
    
        $finDebug = microtime(true);
        Log::info("Tiempo procesar Cierre de ventas (ProcesarVentaCerrada.php): ".round($finDebug - $inicioDebug, 3)." s");
    }
}
