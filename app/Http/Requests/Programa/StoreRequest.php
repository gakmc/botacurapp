<?php

namespace App\Http\Requests\Programa;
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
            'nombre_programa' => ['required', 'string', 'max:255', 'unique:programas'],
            'valor_programa'  => ['numeric'],
            'descuento'       => ['numeric'],
            'espacio_tipo'    => ['nullable', 'in:estacion_economico,estacion_intermedio,estacion_full,terraza,reposera'],
            'min_personas'    => ['nullable', 'integer', 'min:1'],
            'permite_giftcard'=> ['nullable', 'boolean'],
            'solo_plataforma' => ['nullable', 'boolean'],
            'imagenes'        => ['nullable', 'array', 'max:5'],
            'imagenes.*'      => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    public function messages()
    {
        return [
            'nombre_programa.required' => 'El campo de nombre es requerido',
            'valor_programa' => 'Valor númerico',
            'descuento' => 'La descripción es númerica',

        ];
    }
}



