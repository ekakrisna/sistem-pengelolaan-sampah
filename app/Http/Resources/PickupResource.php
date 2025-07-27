<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PickupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'pickup_schedule_id' => $this->pickup_schedule_id,
            'customer_id' => $this->customer_id,
            'petugas_id' => $this->petugas_id,
            'status' => $this->status,
            'note' => $this->note,
        ];
    }
}
