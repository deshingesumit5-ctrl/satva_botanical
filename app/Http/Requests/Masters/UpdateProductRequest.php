<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('product');
        return [
            'code'              => "required|string|max:50|unique:products,code,{$id}",
            'name'              => 'required|string|max:255',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'base_uom_id'       => 'required|exists:uoms,id',
            'description'       => 'nullable|string',
            'shelf_life_days'   => 'nullable|integer|min:0',
            'hsn_code'          => 'nullable|string|max:20',
            'is_active'         => 'boolean',
        ];
    }
}
