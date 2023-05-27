<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $permission = $this->route('permission');
        return $this->user()->can('update', $permission);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles'],
            'description' => ['required', 'string'],
            'role_id' => ['required', 'numeric']
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'El campo de nombre es requerido',
            'name.unique' => 'El nombre ya esta en uso, Intente con otro',
            'description.required' => 'La descripción es requerida',
            'role_id.required' => 'El campo del rol es obligatorio',
            'role_id.numeric' => 'El formato es correcto'
        ];
    }
}
