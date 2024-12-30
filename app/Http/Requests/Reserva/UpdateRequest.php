<?php

namespace App\Http\Requests\Reserva;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'cliente_id'=>['required','exists:clientes,id'],
            'cantidad_personas'=>['required','integer','min:1'],
            'cantidad_masajes'=>['nullable','integer'],
            'fecha_visita'=>['date', 'required'],
            'observacion'=>['nullable', 'string', 'max:255'],
            'id_programa' => ['required', 'exists:programas,id'],

        ];
    }

    public function messages(){
        return [
            'cliente_id.required'=>'Cliente incorrecto, contactese con el administrador',
            'cliente_id.exists'=>'El cliente seleccionado no existe en nuestro registro.',
            'id_programa.required'=>'Debe seleccionar un programa',
            'id_programa.exists'=>'El programa seleccionado no existe en nuestro registro.',
            'cantidad_personas.integer'=>'El campo solo acepta valores numéricos',
            'cantidad_masajes.integer'=>'El campo solo acepta valores numéricos',
            'fecha_visita.required' => 'El campo es obligatorio',
            'fecha_visita.date' => 'El campo debe ser una fecha valida',
            'observacion.max'=>'Excede el máximo de caracteres permitidos'
        ];
    }
}
