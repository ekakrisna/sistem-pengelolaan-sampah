<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WasteTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'admin_id' => $this->admin_id,
            'name' => $this->name,
            'description' => $this->description,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
