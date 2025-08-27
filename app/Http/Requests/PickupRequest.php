<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pickup_schedule_id' => 'integer',
            'customer_id' => 'integer',
            'petugas_id' => 'integer',
            'status' => 'in:scheduled,assigned,completed,canceled',
            'note' => 'string',
            'deleted_at' => 'date',
        ];
    }
}
