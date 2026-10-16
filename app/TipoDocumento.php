<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    protected $auditoriaNombre = 'el tipo de documento';

    protected $auditoriaCampos = [
        'nombre'      => 'Nombre',
        'descripcion' => 'Descripción',
    ];

    protected $table = 'tipos_documentos';

    protected $fillable = [
        'nombre', 'descripcion'
    ];


    //RELACIONES
    public function egresos()
    {
        return $this->belongsTo(Egreso::class,'tipo_documento_id');
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
