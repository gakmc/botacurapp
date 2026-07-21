<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WoocommerceOrder extends Model
{
    protected $table = 'woocommerce_orders';

    protected $fillable = [
        // Identificadores WC
        'wc_order_id',
        'wc_order_key',
        'wc_product_id',
        'cantidad_personas',
        // Cliente
        'billing_email',
        'billing_first_name',
        'billing_last_name',
        'billing_phone',
        // Checkout personalizado
        'fecha_visita_wc',
        'fecha_reservacion_wc',
        // Pedido
        'status',
        'total',
        'currency',
        'payment_method',
        // Transbank Webpay
        'authorization_code',
        'card_number',
        'payment_type',
        'transaction_status',
        'buy_order',
        'installments_number',
        // Resultado Laravel
        'procesado',
        'reserva_id',
        'cliente_id',
        'error_detalle',
        'payload_raw',
    ];

    protected $casts = [
        'payload_raw'          => 'array',
        'fecha_visita_wc'      => 'date',
        'fecha_reservacion_wc' => 'date',
        'installments_number'  => 'integer',
        'total'                => 'integer',
        'cantidad_personas'    => 'integer',
    ];

    protected $dates = [
        'fecha_visita_wc',
        'fecha_reservacion_wc',
    ];

    // ─── Relaciones ───────────────────────────────────────────────

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function programa()
    {
        return $this->belongsTo(Programa::class, 'wc_product_id', 'wc_product_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopePendientes($query)
    {
        return $query->where('procesado', 'pendiente');
    }

    public function scopeConError($query)
    {
        return $query->where('procesado', 'error');
    }

    public function scopeOk($query)
    {
        return $query->where('procesado', 'ok');
    }

    // ─── Accessors ────────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->billing_first_name} {$this->billing_last_name}");
    }

    public function getTieneFechaVisitaAttribute(): bool
    {
        return !is_null($this->fecha_visita_wc);
    }

    /**
     * Retorna el folio tal como se guardó en Venta.folio_abono
     * Ej: "WBP-395382"
     */
    public function getFolioAttribute(): string
    {
        return $this->authorization_code
            ? 'WBP-' . $this->authorization_code
            : 'WC-' . $this->wc_order_id;
    }

    /**
     * Indica si el pago fue con cuotas.
     */
    public function getTieneCuotasAttribute(): bool
    {
        return $this->installments_number > 0;
    }
}
