<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class TipoTransaccion extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    protected $auditoriaNombre = 'el medio de pago';

    protected $auditoriaCampos = ['nombre' => 'Nombre'];

    protected $table = 'tipos_transacciones';

    protected $fillable = [
        'nombre',
    ];

//RELACIONES
    public function ventas()
    {
        return $this->hasMany(Venta::class, 'id_tipo_transaccion');
    }

    public function pagosConsumos()
    {
        return $this->hasMany(PagoConsumo::class, 'id_tipo_transaccion');
    }

// public function reservaciones()
    // {
    //     return $this->belongsToMany('App\Reserva');
    // }

//ALMACENAMIENTO

//VALIDACION

//RECUPERACION DE INFORMACION

//OTRAS OPERACIONES
}
