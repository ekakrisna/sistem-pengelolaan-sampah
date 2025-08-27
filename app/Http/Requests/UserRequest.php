<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeUser = $this->route('user');
        $routeId   = $this->route('id');
        $id        = is_object($routeUser) ? $routeUser->id : ($routeUser ?? $routeId);

        $isUpdate  = in_array($this->method(), ['PUT', 'PATCH'], true);
        return [
            'name' => 'string|max:191',
            'email' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($id)->whereNull('deleted_at'),
            ],
            'phone' => 'string|max:191',
            'email_verified_at' => 'date',
            'password' => 'string|max:191',
            'role' => 'in:admin,petugas,customer,super_admin',
            'remember_token' => 'string|max:100',
            'deleted_at' => 'date',
        ];
    }
}
