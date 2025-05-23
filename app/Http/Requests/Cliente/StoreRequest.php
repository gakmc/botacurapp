<?php

namespace App\Http\Requests\Cliente;

use App\Cliente;
use App\User;
use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Support\Facades\Auth;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // return true;
        return $this->user()->can('create', User::class);

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'nombre_cliente'=>['required', 'string', 'max:255'],
            'whatsapp_cliente'=>['max:12','string', 'nullable', 'unique:clientes'],
            'instagram_cliente'=>['max:255', 'string','nullable'],
            'sexo'=>['required','in:Masculino,Femenino,na'],
            'correo'=>['required', 'string', 'email', 'max:255', 'unique:clientes']
        ];
    }

    public function messages()
    {
        return [
            'nombre_cliente.required' => 'El campo Nombre es requerido',
            'correo.required'=>'Este campo Correo es requerido',
            'correo.string'=>'El valor no es correcto',
            'correo.max'=>'Excede el limite de 255 caracteres',
            'correo.unique'=>'Este email ya esta registrado',
            'whatsapp_cliente.max'=>'Excede el máximo de 12 caracteres',
            'whatsapp_cliente.string'=>'La informacion puede ser alfanumerica',
            'whatsapp_cliente.unique'=>'Este numero ya esta registrado',
            'instagram_cliente.string'=>'La informacion puede ser alfanumerica',
            'sexo.required'=>'El campo Genero es requerido',
            'sexo.in'=>'Debe seleccionar una opcion'

        ];
    }
}
