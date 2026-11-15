<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('equipment');
        return [
            'code'            => "required|string|max:50|unique:equipments,code,{$id}",
            'name'            => 'required|string|max:255',
            'type'            => 'nullable|string|max:100',
            'capacity'        => 'nullable|numeric|min:0',
            'capacity_uom_id' => 'nullable|exists:uoms,id',
            'is_active'       => 'boolean',
        ];
    }
}
