<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('customer');
        return [
            'code'           => "required|string|max:50|unique:customers,code,{$id}",
            'name'           => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'mobile'         => 'nullable|string|max:20',
            'address'        => 'nullable|string|max:500',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:100',
            'country'        => 'nullable|string|max:100',
            'pincode'        => 'nullable|string|max:20',
            'gstin'          => 'nullable|string|max:20',
            'credit_limit'   => 'nullable|numeric|min:0',
            'payment_terms'  => 'nullable|string|max:255',
            'is_active'      => 'boolean',
        ];
    }
}
