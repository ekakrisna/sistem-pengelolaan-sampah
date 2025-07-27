<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PickupScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'waste_type_id' => $this->waste_type_id,
            'date' => $this->date,
            'time_slot' => $this->time_slot,
            'location' => $this->location,
        ];
    }
}
