<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('material');
        return [
            'code'               => "required|string|max:50|unique:materials,code,{$id}",
            'name'               => 'required|string|max:255',
            'type'               => 'required|in:raw,packing,consumable,solvent,reagent,others',
            'base_uom_id'        => 'required|exists:uoms,id',
            'reorder_level'      => 'nullable|numeric|min:0',
            'standard_cost'      => 'nullable|numeric|min:0',
            'storage_conditions' => 'nullable|string|max:500',
            'hsn_code'           => 'nullable|string|max:20',
            'is_active'          => 'boolean',
        ];
    }
}
