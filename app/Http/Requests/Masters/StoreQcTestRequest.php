<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class StoreQcTestRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'      => 'required|string|max:50|unique:qc_tests,code',
            'name'      => 'required|string|max:255',
            'method'    => 'nullable|string|max:255',
            'type'      => 'required|in:physical,chemical,microbiological',
            'is_active' => 'boolean',
        ];
    }
}
