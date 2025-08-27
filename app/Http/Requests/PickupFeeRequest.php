<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PickupFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'village_code' => 'string|max:10',
            'waste_type_id' => 'integer',
            'admin_id' => 'integer',
            'amount' => 'numeric',
            'description' => 'string',
            'deleted_at' => 'date',
        ];
    }
}
