<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('activity');
        return [
            'code'        => "required|string|max:50|unique:activities,code,{$id}",
            'name'        => 'required|string|max:255',
            'stage_id'    => 'nullable|exists:stages,id',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ];
    }
}
