<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WasteTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_id' => 'integer',
            'name' => 'string|max:191',
            'description' => 'string',
            'deleted_at' => 'date',
        ];
    }
}
