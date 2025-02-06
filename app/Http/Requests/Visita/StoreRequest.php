<?php

namespace App\Http\Requests\Visita;

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
            'horario_sauna' => 'nullable|string', // Para 1 horario SPA
            'horario_masaje' => 'nullable|string', // Caso de datos simples
            'tipo_masaje' => 'nullable|string',
            'id_ubicacion' => 'nullable|string',
            'trago_cortesia' => 'required|string',
            'spas.*.horario_sauna' => 'nullable|string', // Para arreglos de SPA
            'masajes.*.horario_masaje' => 'nullable|string', // Para arreglos de masajes
            'masajes.*.tipo_masaje' => 'nullable|string',
            'masajes.*.id_lugar_masaje' => 'nullable|string',
            'menus.*.id_producto_entrada' => 'nullable|integer',
            'menus.*.id_producto_fondo' => 'nullable|string',
            'menus.*.id_producto_acompanamiento' => 'nullable|integer',
            'menus.*.alergias' => 'nullable|string',
            'menus.*.observacion' => 'nullable|string',
        ];
    }
}
