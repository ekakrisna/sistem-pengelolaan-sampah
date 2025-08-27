<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'customer_id' => $this->customer_id,
            'number' => $this->number,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'currency' => $this->currency,
            'due_at' => $this->due_at,
            'expires_at' => $this->expires_at,
            'description' => $this->description,
            'meta' => $this->meta,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
