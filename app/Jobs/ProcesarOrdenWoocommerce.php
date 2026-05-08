<?php

namespace App\Jobs;

use App\Cliente;
use App\Programa;
use App\Reserva;
use App\Venta;
use App\WoocommerceOrder;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcesarOrdenWoocommerce implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries      = 3;
    public $retryAfter = 60;

    protected $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    // ─────────────────────────────────────────────────────────────
    //  HANDLE PRINCIPAL
    // ─────────────────────────────────────────────────────────────

    public function handle()
    {
        $payload   = $this->payload;
        $wcOrderId = $payload['id'] ?? null;

        Log::info("[WC-Webhook] Procesando orden #{$wcOrderId}");

        $wcOrder = WoocommerceOrder::where('wc_order_id', $wcOrderId)->first();

        if (!$wcOrder) {
            Log::warning("[WC-Webhook] No se encontró registro para orden #{$wcOrderId}");
            return;
        }

        try {
            DB::transaction(function () use ($payload, $wcOrder) {

                // 1) Extraer todos los campos del pedido real
                $campos = $this->extraerCampos($payload);

                Log::info("[WC-Webhook] Campos extraídos", [
                    'correo'        => $campos['correo'],
                    'fecha_visita'  => $campos['fecha_visita'],
                    'cantidad'      => $campos['cantidad_personas'],
                    'total'         => $campos['total'],
                    'auth_code'     => $campos['authorization_code'],
                    'payment_type'  => $campos['payment_type'],
                ]);

                // 2) Resolver cliente (buscar o crear)
                $cliente = $this->resolverCliente($campos);

                // 3) Resolver programa por wc_product_id
                $programa = $this->resolverPrograma($payload);

                if (!$programa) {
                    $wcProductId = $payload['line_items'][0]['product_id'] ?? 'null';
                    throw new \Exception(
                        "No se encontró Programa con wc_product_id = {$wcProductId}. " .
                        "Asigna el wc_product_id al programa en el backoffice."
                    );
                }

                // 4) Crear reserva
                $reserva = $this->crearReserva($cliente, $programa, $campos, $payload);

                // 5) Crear venta con datos reales de Transbank
                $venta = $this->crearVenta($reserva, $campos);

                // 6) Actualizar log como procesado OK
                $wcOrder->update([
                    'procesado'             => 'ok',
                    'reserva_id'            => $reserva->id,
                    'cliente_id'            => $cliente->id,
                    'fecha_visita_wc'       => $campos['fecha_visita'],
                    'authorization_code'    => $campos['authorization_code'],
                    'payment_type'          => $campos['payment_type'],
                    'transaction_status'    => $campos['transaction_status'],
                    'card_number'           => $campos['card_number'],
                ]);

                Log::info(
                    "[WC-Webhook] ✓ Orden #{$wcOrder->wc_order_id} OK → " .
                    "Reserva #{$reserva->id} | Cliente #{$cliente->id} | " .
                    "Fecha visita: {$campos['fecha_visita']} | " .
                    "Auth: {$campos['authorization_code']}"
                );
            });

        } catch (\Throwable $e) {
            Log::error("[WC-Webhook] ✗ Error en orden #{$wcOrderId}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $wcOrder->update([
                'procesado'     => 'error',
                'error_detalle' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  EXTRAER CAMPOS DEL PAYLOAD REAL
    //
    //  Basado en el pedido real #9358:
    //  - billing[]           → datos del cliente
    //  - meta_data[]         → billing_fecha_visita, authorizationCode,
    //                          cardNumber, paymentType, transactionStatus,
    //                          amount, buyOrder, transactionDate, etc.
    //  - line_items[]        → producto, cantidad, precio unitario
    // ─────────────────────────────────────────────────────────────

    private function extraerCampos(array $payload): array
    {
        $billing   = $payload['billing']    ?? [];
        $lineItems = $payload['line_items'] ?? [];

        // Convertir meta_data a array key → value
        // WC envía: [ {"key":"billing_fecha_visita","value":"2024-10-27"}, ... ]
        $meta = $this->parsearMetaData($payload['meta_data'] ?? []);

        // ── Datos del cliente ────────────────────────────────────
        $nombre = trim(
            ($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')
        );

        $correo   = strtolower(trim($billing['email'] ?? ''));
        $telefono = $this->normalizarTelefono($billing['phone'] ?? '');

        // ── Fecha de visita ──────────────────────────────────────
        // Confirmado: viene en meta_data con key "billing_fecha_visita"
        // Formato real: "2024-10-27" (Y-m-d) → no necesita conversión
        $fechaVisitaRaw = $meta['billing_fecha_visita']
            ?? $billing['billing_fecha_visita']
            ?? $billing['fecha_visita']
            ?? null;

        $fechaVisita = $this->parsearFecha($fechaVisitaRaw);

        // ── Cantidad de personas ─────────────────────────────────
        // Confirmado: quantity = 2 en el pedido real
        $cantidad = (int) ($lineItems[0]['quantity'] ?? 1);

        // ── Total pagado ─────────────────────────────────────────
        // Confirmado: amount en meta_data = 60000, total en payload = "60000.00"
        // Usamos el total del pedido como fuente principal
        $total = (int) ($payload['total'] ?? $meta['amount'] ?? 0);

        // ── Datos de Transbank Webpay ────────────────────────────
        // Confirmado en campos personalizados del pedido real #9358:
        //   authorizationCode  → "395382"     (usamos como folio_abono)
        //   cardNumber         → "4223"        (últimos 4 dígitos)
        //   paymentType        → "Crédito"
        //   transactionStatus  → "Autorizada"
        //   transactionDate    → "24-10-2024 17:30:45 +00:00"
        //   buyOrder           → "wcef25dfbf51101f7202:9358"
        //   installmentsNumber → "0"
        $authorizationCode   = $meta['authorizationCode']  ?? null;
        $cardNumber          = $meta['cardNumber']          ?? null;
        $paymentType         = $meta['paymentType']         ?? $payload['payment_method_title'] ?? null;
        $transactionStatus   = $meta['transactionStatus']   ?? null;
        $transactionDate     = $meta['transactionDate']     ?? null;
        $buyOrder            = $meta['buyOrder']            ?? null;
        $installmentsNumber  = (int) ($meta['installmentsNumber'] ?? 0);

        return [
            // Cliente
            'nombre'                => $nombre ?: 'Cliente WooCommerce',
            'correo'                => $correo,
            'telefono'              => $telefono,

            // Reserva
            'fecha_visita'          => $fechaVisita,
            'cantidad_personas'     => $cantidad,
            'wc_order_id'           => $payload['id'] ?? null,

            // Venta / Pago
            'total'                 => $total,
            'authorization_code'    => $authorizationCode,   // folio_abono
            'card_number'           => $cardNumber,
            'payment_type'          => $paymentType,
            'transaction_status'    => $transactionStatus,
            'transaction_date'      => $transactionDate,
            'buy_order'             => $buyOrder,
            'installments_number'   => $installmentsNumber,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  RESOLVER CLIENTE
    // ─────────────────────────────────────────────────────────────

    private function resolverCliente(array $campos)
    {
        $correo = $campos['correo'];

        $cliente = Cliente::where('correo', $correo)->first();

        if ($cliente) {
            Log::info("[WC-Webhook] Cliente existente: {$correo} (id #{$cliente->id})");

            // Actualizar teléfono si estaba vacío
            if (empty($cliente->whatsapp_cliente) && !empty($campos['telefono'])) {
                $cliente->update(['whatsapp_cliente' => $campos['telefono']]);
            }

            return $cliente;
        }

        $cliente = Cliente::create([
            'nombre_cliente'    => $campos['nombre'],
            'correo'            => $correo,
            'whatsapp_cliente'  => $campos['telefono'],
            'instagram_cliente' => '',
            'sexo'              => '',
        ]);

        Log::info("[WC-Webhook] Cliente creado: {$correo} (id #{$cliente->id})");

        return $cliente;
    }

    // ─────────────────────────────────────────────────────────────
    //  RESOLVER PROGRAMA
    // ─────────────────────────────────────────────────────────────

    private function resolverPrograma(array $payload)
    {
        $wcProductId = $payload['line_items'][0]['product_id'] ?? null;

        if (!$wcProductId) {
            return null;
        }

        return Programa::where('wc_product_id', $wcProductId)->first();
    }

    // ─────────────────────────────────────────────────────────────
    //  CREAR RESERVA
    // ─────────────────────────────────────────────────────────────

    private function crearReserva(
        Cliente  $cliente,
        Programa $programa,
        array    $campos,
        array    $payload
    ) {

        return Reserva::create([
            'cliente_id'        => $cliente->id,
            'id_programa'       => $programa->id,
            'cantidad_personas' => $campos['cantidad_personas'],
            'fecha_visita'      => $campos['fecha_visita'],
            'user_id'           => config('woocommerce.system_user_id', 1),
            'cantidad_masajes'  => $programa->incluye_masajes
                                    ? $campos['cantidad_personas']
                                    : null,
            'observacion'       => $this->construirObservacion($campos),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  CREAR VENTA
    //
    //  Usa el authorizationCode de Transbank como folio_abono.
    //  El total pagado se registra tanto en abono_programa como
    //  en total_pagar (pago completo desde WC).
    // ─────────────────────────────────────────────────────────────

    private function crearVenta(Reserva $reserva, array $campos)
    {
        // Buscar tipo de transacción para Webpay / Transbank
        // Ajusta los términos según los nombres en tu tabla tipo_transacciones
        $tipoTransaccion = \App\TipoTransaccion::where('nombre', 'like', '%webpay%')
            ->orWhere('nombre', 'like', '%transbank%')
            ->orWhere('nombre', 'like', '%tarjeta%')
            ->orWhere('nombre', 'like', '%credito%')
            ->orWhere('nombre', 'like', '%débito%')
            ->orWhere('nombre', 'like', '%transferencia%')
            ->first();

        // Folio: usamos authorizationCode de Transbank (ej: "395382")
        // Si no existe, fallback al ID de la orden WC
        $folio = $campos['authorization_code']
            ? 'WBP-' . $campos['authorization_code']
            : 'WC-' . $campos['wc_order_id'];

        return Venta::create([
            'id_reserva'                => $reserva->id,
            'abono_programa'            => $campos['total'],
            'total_pagar'               => $campos['total'],
            'folio_abono'               => $folio,
            'id_tipo_transaccion_abono' => $tipoTransaccion ? $tipoTransaccion->id : null,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  CONSTRUIR OBSERVACIÓN
    //
    //  Genera un texto rico para el campo observacion de la reserva
    //  con los datos del pago Transbank para trazabilidad.
    // ─────────────────────────────────────────────────────────────

    private function construirObservacion(array $campos)
    {
        $partes = [
            'Reserva generada automáticamente desde WooCommerce.',
            'Orden WC #' . $campos['wc_order_id'],
        ];

        if ($campos['authorization_code']) {
            $partes[] = 'Auth Transbank: ' . $campos['authorization_code'];
        }

        if ($campos['payment_type']) {
            $partes[] = 'Tipo pago: ' . $campos['payment_type'];
        }

        if ($campos['card_number']) {
            $partes[] = 'Tarjeta: **** ' . $campos['card_number'];
        }

        if ($campos['installments_number'] > 0) {
            $partes[] = 'Cuotas: ' . $campos['installments_number'];
        }

        return implode(' | ', $partes);
    }

    // ─────────────────────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────────────────────

    /**
     * Convierte el array meta_data de WooCommerce en un array asociativo
     * key => value para acceso directo.
     *
     * Formato WC: [ {"id":1,"key":"billing_fecha_visita","value":"2024-10-27"}, ... ]
     */
    private function parsearMetaData(array $metaData)
    {
        $meta = [];
        foreach ($metaData as $item) {
            if (isset($item['key']) && strpos($item['key'], '_') !== 0) {
                // Ignorar keys internas de WP que empiezan con _
                $meta[$item['key']] = $item['value'] ?? null;
            }
        }
        return $meta;
    }

    /**
     * Parsea la fecha al formato Y-m-d.
     * Confirmado formato real de WC: "2024-10-27" (Y-m-d) → ya es correcto.
     */
    private function parsearFecha(?string $fechaRaw)
    {
        if (empty($fechaRaw)) {
            return null;
        }
        try {
            // Formato ISO Y-m-d (confirmado en pedido real)
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($fechaRaw))) {
                return $fechaRaw;
            }
            // Fallback para otros formatos posibles
            return Carbon::parse($fechaRaw)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning("[WC-Webhook] No se pudo parsear fecha: '{$fechaRaw}'");
            return null;
        }
    }

    /**
     * Normaliza teléfono al formato 56XXXXXXXXX.
     * Confirmado formato real: "+56988092455" → "56988092455"
     */
    private function normalizarTelefono(string $telefono)
    {
        if (empty($telefono)) {
            return '';
        }

        $numero = preg_replace('/\D/', '', $telefono);

        // +56988092455 → 56988092455 (ya está correcto)
        if (strlen($numero) === 11 && substr($numero, 0, 2) === '56') {
            return $numero;
        }

        // 988092455 (9 dígitos empezando en 9)
        if (strlen($numero) === 9 && substr($numero, 0, 1) === '9') {
            return '56' . $numero;
        }

        // 88092455 (8 dígitos sin el 9)
        if (strlen($numero) === 8) {
            return '569' . $numero;
        }

        return $numero;
    }
}
