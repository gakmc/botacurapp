<?php

namespace App\Http\Requests\Programa;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
            'nombre_programa' => ['required', 'string', 'max:255', Rule::unique('programas')->ignore($this->route('programa'))],
            'valor_programa'  => ['numeric'],
            'descuento'       => ['numeric'],
            'espacio_tipo'    => ['nullable', 'in:estacion_economico,estacion_intermedio,estacion_full,terraza,reposera,wellness'],
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

    /**
     * DIAGNÓSTICO TEMPORAL — registra el error real de PHP al recibir el
     * archivo (UPLOAD_ERR_*) cuando "imagenes" falla la validación, para
     * identificar por qué la regla implícita "uploaded" rechaza el upload.
     * Quitar este override una vez encontrada la causa.
     */
    protected function failedValidation(Validator $validator)
    {
        if ($validator->errors()->has('imagenes') || $validator->errors()->has('imagenes.*')) {
            foreach ($this->file('imagenes', []) as $i => $file) {
                if ($file) {
                    Log::warning('[WC-Images][DIAG] Falla de validación de imagen', [
                        'index'         => $i,
                        'original_name' => $file->getClientOriginalName(),
                        'client_size'   => $file->getSize(),
                        'is_valid'      => $file->isValid(),
                        'upload_error'  => $file->getError(),
                        'error_message' => $file->getErrorMessage(),
                    ]);
                } else {
                    Log::warning("[WC-Images][DIAG] Falla de validación de imagen: índice {$i} llegó sin archivo (probablemente excedió post_max_size o se cortó la subida)");
                }
            }
        }

        parent::failedValidation($validator);
    }
}
