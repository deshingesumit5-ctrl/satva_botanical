<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class StoreParameterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'      => 'required|string|max:50|unique:parameters,code',
            'name'      => 'required|string|max:255',
            'uom_id'    => 'nullable|exists:uoms,id',
            'data_type' => 'required|in:numeric,text,boolean',
            'is_active' => 'boolean',
        ];
    }
}
