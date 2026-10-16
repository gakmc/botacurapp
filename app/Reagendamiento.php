<?php

namespace App;

use Carbon\Carbon;
use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Reagendamiento extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    protected function auditoriaContexto()
    {
        return $this->reserva;
    }

    protected $auditoriaCampos = [
        'fecha_original' => ['Fecha original', 'fecha'],
        'nueva_fecha'    => ['Nueva fecha', 'fecha'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        if ($evento === Auditoria::EVENTO_CREO) {
            return 'reagendó ' . $this->textoReservaAuditoria($this->reserva)
                . ' del ' . $this->fecha_original . ' al ' . $this->nueva_fecha;
        }

        return $this->segunEvento($evento, 'creó', 'editó', 'eliminó') . ' un reagendamiento de ' . $this->textoReservaAuditoria($this->reserva);
    }

    protected $fillable = [
        "nueva_fecha",
        "fecha_original",
        "id_reserva"

    ];

    //RELACIONES

    public function reserva(){
        return $this->belongsTo(Reserva::class,'id_reserva');
    }


   //RECUPERACION DE INFORMACION
    public function getFechaOriginalAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function getNuevaFechaAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }
}
