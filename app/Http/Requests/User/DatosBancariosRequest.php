<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class DatosBancariosRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules()
    {
        return [
            'rut' => ['nullable', 'string', 'max:15'],
            'boletea' => ['nullable', 'boolean'],
            'banco' => ['nullable', 'string', 'max:60'],
            'tipo_cuenta_bancaria' => ['nullable', 'string', 'max:30'],
            'numero_cuenta_bancaria' => ['nullable', 'string', 'max:30'],
            'correo_personal' => ['nullable', 'string', 'email', 'max:100'],
        ];
    }

    public function messages()
    {
        return [
            'rut.max' => 'El RUT es demasiado largo.',
            'banco.max' => 'El nombre del banco es demasiado largo.',
            'tipo_cuenta_bancaria.max' => 'El tipo de cuenta es demasiado largo.',
            'numero_cuenta_bancaria.max' => 'El número de cuenta es demasiado largo.',
            'correo_personal.email' => 'El correo personal no es válido.',
            'correo_personal.max' => 'El correo personal es demasiado largo.',
        ];
    }

    /**
     * Normaliza el RUT antes de validar: sin puntos, guión, DV en mayúscula.
     */
    protected function prepareForValidation()
    {
        if ($this->filled('rut')) {
            $rut = strtoupper(str_replace(['.', ' '], '', $this->rut));
            $this->merge(['rut' => $rut]);
        }
    }
}
