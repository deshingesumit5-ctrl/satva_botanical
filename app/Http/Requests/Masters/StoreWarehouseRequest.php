<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'     => 'required|string|max:50|unique:warehouses,code',
            'name'     => 'required|string|max:255',
            'plant_id' => 'required|exists:plants,id',
            'type'     => 'required|in:raw_material,wip,finished_goods,quarantine',
            'is_active'=> 'boolean',
        ];
    }
}
