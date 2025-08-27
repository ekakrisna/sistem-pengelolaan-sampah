<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'integer',
            'number' => 'string|max:191|unique:transactions,number',
            'status' => 'in:draft,pending,paid,partially_paid,expired,canceled,refunded',
            'subtotal' => 'numeric',
            'discount_amount' => 'numeric',
            'tax_amount' => 'numeric',
            'total' => 'numeric',
            'currency' => 'string|max:8',
            'due_at' => 'date',
            'expires_at' => 'date',
            'description' => 'string',
            'meta' => 'json',
            'deleted_at' => 'date',
        ];
    }
}
