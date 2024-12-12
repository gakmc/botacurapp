<?php

namespace App\Http\Requests\Servicio;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'nombre_servicio' => ['required', 'string', 'max:20'],
            'valor_servicio' => ['required','integer'],
            'costo_servicio' => ['nullable','integer'],
            'duracion' => ['required', 'integer']
        ];
    }

    public function messages(){
        return [
            'nombre_servicio.required' => 'El campo es requerido.',
            'nombre_servicio.max' => 'Excede el límite de 20 carácteres como maximo',
            'valor_servicio.required' => 'El campo es requerido',
            'valor_servicio.integer' => 'El valor debe ser numérico',
            'costo_servicio.integer' => 'El valor debe ser numérico',
            'duracion.required' => 'El campo es requerido',
            'duracion.integer' => 'El valor debe ser numérico'
        ];
    }
}
