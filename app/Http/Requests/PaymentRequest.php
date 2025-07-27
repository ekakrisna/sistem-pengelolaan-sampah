<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'integer',
            'amount' => 'numeric',
            'status' => 'in:pending,paid,failed',
            'payment_method' => 'string|max:191',
            'proof_image' => 'string|max:191',
            'paid_at' => 'date',
        ];
    }
}
