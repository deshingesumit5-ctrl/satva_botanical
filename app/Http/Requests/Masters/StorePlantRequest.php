<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class StorePlantRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'     => 'required|string|max:50|unique:plants,code',
            'name'     => 'required|string|max:255',
            'address'  => 'nullable|string|max:500',
            'city'     => 'nullable|string|max:100',
            'state'    => 'nullable|string|max:100',
            'country'  => 'nullable|string|max:100',
            'pincode'  => 'nullable|string|max:20',
            'gstin'    => 'nullable|string|max:20',
            'is_active'=> 'boolean',
        ];
    }
}
