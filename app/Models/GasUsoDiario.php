<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para gas_uso_diario (BD IoT).
 * Registra horas de encendido calefont por día y sector.
 *
 * tinaja_1/2 → horas reales de calefont encendido (sensor temp. sonoff)
 * gas_casa/cocina → días en uso (siempre 1.0 por registro diario)
 */
class GasUsoDiario extends Model
{
    protected $connection = 'mysql_iot';
    protected $table      = 'gas_uso_diario';

    protected $fillable = [
        'lugar',
        'fecha',
        'horas_uso',
        'unidad',
        'gas_instalacion_id',
        'origen',
        'observacion',
    ];

    protected $casts = [
        'fecha'     => 'date',
        'horas_uso' => 'decimal:4',
    ];

    /**
     * Suma total de horas desde una fecha hasta hoy para un lugar.
     */
    public static function totalDesde(string $lugar, string $fechaDesde): float
    {
        return (float) static::where('lugar', $lugar)
            ->where('fecha', '>=', $fechaDesde)
            ->sum('horas_uso');
    }

    /**
     * Registro de hoy para un lugar.
     */
    public static function hoy(string $lugar): ?self
    {
        return static::where('lugar', $lugar)
            ->where('fecha', now()->toDateString())
            ->first();
    }
}
