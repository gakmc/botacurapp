<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use RealRashid\SweetAlert\Facades\Alert;

class Cliente extends Model
{
    use Auditable;

    /** Campos que envían los formularios de crear/editar cliente */
    const CAMPOS_FORMULARIO = ['nombre_cliente', 'whatsapp_cliente', 'instagram_cliente', 'sexo', 'correo'];

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    protected $auditoriaCampos = [
        'nombre_cliente' => 'Nombre',
        'sexo'           => 'Sexo',
        'bloqueado'      => ['Bloqueado', 'bool'],
    ];

    // Datos de contacto: solo se registra que cambiaron
    protected $auditoriaCamposOcultos = [
        'whatsapp_cliente'  => 'WhatsApp',
        'instagram_cliente' => 'Instagram',
        'correo'            => 'Correo',
    ];

    protected function auditoriaDescripcion($evento)
    {
        if ($evento === Auditoria::EVENTO_EDITO && $this->isDirty('bloqueado') && count(array_intersect_key($this->getDirty(), $this->auditoriaCampos + $this->auditoriaCamposOcultos)) === 1) {
            return ($this->bloqueado ? 'bloqueó' : 'desbloqueó') . ' al cliente ' . $this->nombre_cliente;
        }

        return $this->segunEvento($evento, 'creó', 'editó', 'eliminó') . ' el cliente ' . $this->nombre_cliente;
    }

    protected $fillable = [
        'nombre_cliente', 'whatsapp_cliente','instagram_cliente','sexo','correo','bloqueado'
    ];

    protected $casts = [
        'bloqueado' => 'boolean',
    ];




    //RELACIONES
    public function reservas()
    {
        return $this->hasMany('App\Reserva');
    }



    //ALMACENAMIENTO

    public function store($request){
        if ($request->has('whatsapp_cliente')) {
            $whatsapp = str_replace('+','',$request->whatsapp_cliente);

            if (substr($whatsapp,0,2) !== '56') {
                $whatsapp = '56'.$whatsapp;
            }

            $request->merge(['whatsapp_cliente' => $whatsapp]);
        }

        Alert::success('Exito', 'Cliente guardado')->showConfirmButton();
        return self::create($request->only(self::CAMPOS_FORMULARIO));
    }

    public function my_update($request)
    {
        Alert::success('Éxito', 'Cliente actualizado')->showConfirmButton();
        // Solo los campos del formulario (antes $request->all())
        return self::update($request->only(self::CAMPOS_FORMULARIO));
        
    }

    //VALIDACION

    //RECUPERACION DE INFORMACION

    //OTRAS OPERACIONES
    public function getListaNegraAttribute()
    {
        return $this->bloqueado;
    }
    
}
