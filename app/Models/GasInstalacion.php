<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla gas_instalaciones (BD IoT: botacura_iot).
 * Registra el historial operativo de instalación/cambio de cilindros por lugar.
 */
class GasInstalacion extends Model
{
    protected $connection = 'mysql_iot';   // BD IoT (botacura_iot)
    protected $table      = 'gas_instalaciones';

    protected $fillable = [
        'lugar',
        'fecha_instalacion',
        'fecha_instalacion_anterior',
        'dias_duracion_anterior',
        'valor_cilindro_clp',
        'kg_cilindro',
        'proveedor_nombre',
        'documento',
        'observacion',
        'gas_compra_id',
        'egreso_id',
        'contador_anterior_valor',
        'contador_anterior_unidad',
        'origen',
        'estado',
    ];

    protected $casts = [
        'fecha_instalacion'          => 'datetime',
        'fecha_instalacion_anterior' => 'datetime',
        'dias_duracion_anterior'     => 'integer',
        'valor_cilindro_clp'         => 'integer',
        'kg_cilindro'                => 'decimal:2',
        'contador_anterior_valor'    => 'decimal:2',
    ];

    /**
     * Lugares válidos para la instalación.
     */
    const LUGARES = ['tinaja_1', 'tinaja_2', 'gas_casa', 'gas_cocina'];

    /**
     * Obtiene el último registro instalado en un lugar dado.
     */
    public static function ultimoEnLugar(string $lugar): ?self
    {
        return static::where('lugar', $lugar)
                     ->orderBy('fecha_instalacion', 'desc')
                     ->first();
    }

    /**
     * Calcula los días de duración respecto a la instalación anterior.
     * Retorna null si no hay instalación anterior.
     */
    public function calcularDiasDuracion(): ?int
    {
        if (!$this->fecha_instalacion_anterior) {
            return null;
        }

        return $this->fecha_instalacion_anterior
                    ->diffInDays($this->fecha_instalacion);
    }
}
