<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PickupScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'admin_id' => $this->admin_id,
            'waste_type_id' => $this->waste_type_id,
            'day_of_week' => $this->day_of_week,
            'start_pickup_time' => $this->start_pickup_time,
            'end_pickup_time' => $this->end_pickup_time,
            'village_code' => $this->village_code,
            'quota' => $this->quota,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
