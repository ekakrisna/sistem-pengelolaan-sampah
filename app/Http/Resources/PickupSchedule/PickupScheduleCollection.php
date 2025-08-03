<?php

namespace App\Http\Resources\PickupSchedule;

use App\Data\PickupScheduleData;
use App\Data\UserData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PickupScheduleCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'pickup_schedules' => PickupScheduleData::collect($this->collection),
            'pagination' => [
                'total' => $this->total(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ],
        ];
    }

    public function with(Request $request): array
    {
        // Prevent default "links" and "meta" from being appended
        return [];
    }
}
