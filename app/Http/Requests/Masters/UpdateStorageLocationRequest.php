<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStorageLocationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('storage_location');
        return [
            'code'         => "required|string|max:50|unique:storage_locations,code,{$id}",
            'name'         => 'required|string|max:255',
            'warehouse_id' => 'required|exists:warehouses,id',
            'rack'         => 'nullable|string|max:50',
            'bin'          => 'nullable|string|max:50',
            'is_active'    => 'boolean',
        ];
    }
}
