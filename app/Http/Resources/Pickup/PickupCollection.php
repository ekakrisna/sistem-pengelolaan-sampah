<?php

namespace App\Http\Resources\Pickup;

use App\Data\PickupData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PickupCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'pickups' => PickupData::collect($this->collection),
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
