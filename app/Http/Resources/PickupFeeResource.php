<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PickupFeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'village_code' => $this->village_code,
            'waste_type_id' => $this->waste_type_id,
            'admin_id' => $this->admin_id,
            'amount' => $this->amount,
            'description' => $this->description,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
