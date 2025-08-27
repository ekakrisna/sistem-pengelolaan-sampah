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
            'admin_id' => 'integer',
            'waste_type_id' => 'integer',
            'day_of_week' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_pickup_time' => 'string',
            'end_pickup_time' => 'string',
            'village_code' => 'string|max:10',
            'quota' => 'integer',
            'deleted_at' => 'date',
        ];
    }
}
