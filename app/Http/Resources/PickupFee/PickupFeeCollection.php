<?php

namespace App\Http\Resources\PickupFee;

use App\Data\PickupFeeData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PickupFeeCollection extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'pickup_fees' => PickupFeeData::collect($this->collection),
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
