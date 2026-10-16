<?php
namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    protected $auditoriaNombre = 'el sector';

    protected $auditoriaCampos = ['nombre' => 'Nombre'];

    protected $table = 'sectores';

    protected $fillable = [
        'nombre',
    ];

//RELACIONES
    public function tiposProductos()
    {
        return $this->hasMany('App\TipoProducto');
    }

    public function insumos()
    {
        return $this->hasMany(Insumo::class, 'id_sector');
    }

    public function inventarioMovimientos()
    {
        return $this->hasMany(InventarioMovimiento::class, 'id_sector');
    }



//ALMACENAMIENTO

//VALIDACION

//RECUPERACION DE INFORMACION

//OTRAS OPERACIONES
}
