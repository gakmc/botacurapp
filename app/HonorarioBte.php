<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\HonorarioBte
 *
 * @property int         $id
 * @property string      $folio
 * @property string      $periodo         YYYYMM
 * @property string|null $estado
 * @property string      $rut_emisor
 * @property string|null $nombre_emisor
 * @property string      $fecha_emision   YYYY-MM-DD
 * @property string|null $fecha_pago      YYYY-MM-DD
 * @property int         $monto_bruto
 * @property float       $tasa_retencion
 * @property int         $monto_retenido
 * @property int         $monto_pagado
 * @property int|null    $proveedor_id
 * @property int|null    $egreso_id
 * @property \Carbon\Carbon|null $sincronizado_at
 */
class HonorarioBte extends Model
{
    protected $table = 'honorarios_bte';

    protected $fillable = [
        'folio',
        'periodo',
        'estado',
        'rut_emisor',
        'nombre_emisor',
        'fecha_emision',
        'fecha_pago',
        'monto_bruto',
        'tasa_retencion',
        'monto_retenido',
        'monto_pagado',
        'proveedor_id',
        'egreso_id',
        'sincronizado_at',
    ];

    protected $casts = [
        'monto_bruto'    => 'integer',
        'monto_retenido' => 'integer',
        'monto_pagado'   => 'integer',
        'tasa_retencion' => 'float',
        'fecha_emision'  => 'date',
        'fecha_pago'     => 'date',
        'sincronizado_at'=> 'datetime',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function egreso()
    {
        return $this->belongsTo(Egreso::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePeriodo($query, string $periodo)
    {
        return $query->where('periodo', $periodo);
    }

    public function scopeAnio($query, int $anio)
    {
        return $query->where('periodo', 'like', $anio . '%');
    }

    public function scopeVigentes($query)
    {
        return $query->where('estado', '!=', 'Anulada');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** RUT formateado con DV si no lo tiene */
    public function getRutFormateadoAttribute(): string
    {
        $rut = $this->rut_emisor;
        if (strpos($rut, '-') === false && strlen($rut) > 1) {
            $dv   = strtoupper(substr($rut, -1));
            $body = substr($rut, 0, -1);
            return number_format((int)$body, 0, ',', '.') . '-' . $dv;
        }
        $partes = explode('-', $rut);
        return number_format((int)$partes[0], 0, ',', '.') . '-' . ($partes[1] ?? '');
    }

    /** Monto bruto formateado en pesos */
    public function getMontoBrutoFormateadoAttribute(): string
    {
        return '$' . number_format($this->monto_bruto, 0, ',', '.');
    }

    public function getMontoRetenidoFormateadoAttribute(): string
    {
        return '$' . number_format($this->monto_retenido, 0, ',', '.');
    }

    public function getMontoPagadoFormateadoAttribute(): string
    {
        return '$' . number_format($this->monto_pagado, 0, ',', '.');
    }
}
