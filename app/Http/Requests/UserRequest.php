<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'string|max:191',
            'email' => 'string|max:191|unique:users,email',
            'phone' => 'string|max:191',
            'email_verified_at' => 'date',
            'password' => 'string|max:191',
            'role' => 'in:admin,petugas,customer,super_admin',
            'province_id' => 'string|max:2',
            'city_id' => 'string|max:4',
            'district_id' => 'string|max:7',
            'village_id' => 'string|max:10',
            'address_detail' => 'string',
            'remember_token' => 'string|max:100',
        ];
    }
}
