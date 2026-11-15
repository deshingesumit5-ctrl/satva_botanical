<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class StoreUomRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'         => 'required|string|max:20|unique:uoms,code',
            'name'         => 'required|string|max:100',
            'symbol'       => 'required|string|max:20',
            'type'         => 'required|in:stock,process',
            'is_stock_unit'=> 'boolean',
            'is_active'    => 'boolean',
        ];
    }
}
