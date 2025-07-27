<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PickupScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'waste_type_id' => 'integer',
            'date' => 'date',
            'time_slot' => 'string|max:191',
            'location' => 'string|max:191',
        ];
    }
}
