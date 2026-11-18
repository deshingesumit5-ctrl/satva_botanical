<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('supplier');
        return [
            'code'           => "required|string|max:50|unique:suppliers,code,{$id}",
            'name'           => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'mobile'         => 'nullable|digits:10',
            'pan'            => ['nullable', 'string', 'size:10', 'regex:/^[A-Za-z0-9]{10}$/'],
            'address'        => 'nullable|string|max:500',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:100',
            'country'        => 'nullable|string|max:100',
            'pincode'        => 'nullable|string|max:20',
            'gstin'          => 'nullable|string|max:20',
            'payment_terms'  => 'nullable|string|max:255',
            'is_active'      => 'boolean',
        ];
    }
}
