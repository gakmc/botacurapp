<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FechaDisponible extends Model
{
    protected $fillable = ['fecha', 'tipo', 'habilitada', 'nota'];

    protected $casts = [
        'fecha'     => 'date',
        'habilitada' => 'boolean',
    ];

    /**
     * Genera automáticamente los jueves-domingos de los próximos N días
     * si no existen aún en la tabla.
     */
    public static function sincronizarRegulares(int $dias = 120): void
    {
        $diasSemana = [0, 4, 5, 6]; // Dom, Jue, Vie, Sáb
        $hoy = now()->startOfDay();

        for ($i = 1; $i <= $dias; $i++) {
            $fecha = $hoy->copy()->addDays($i);
            if (in_array($fecha->dayOfWeek, $diasSemana)) {
                self::firstOrCreate(
                    ['fecha' => $fecha->toDateString()],
                    ['tipo' => 'regular', 'habilitada' => true]
                );
            }
        }
    }
}
