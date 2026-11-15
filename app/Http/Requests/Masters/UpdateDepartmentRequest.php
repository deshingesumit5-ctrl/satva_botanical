<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('department');
        return [
            'code'     => "required|string|max:50|unique:departments,code,{$id}",
            'name'     => 'required|string|max:255',
            'plant_id' => 'required|exists:plants,id',
            'is_active'=> 'boolean',
        ];
    }
}
