<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('warehouse');
        return [
            'code'     => "required|string|max:50|unique:warehouses,code,{$id}",
            'name'     => 'required|string|max:255',
            'plant_id' => 'required|exists:plants,id',
            'type'     => 'required|in:raw_material,wip,finished_goods,quarantine',
            'is_active'=> 'boolean',
        ];
    }
}
