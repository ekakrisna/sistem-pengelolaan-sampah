<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'email_verified_at' => $this->email_verified_at,
            'password' => $this->password,
            'role' => $this->role,
            'remember_token' => $this->remember_token,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
