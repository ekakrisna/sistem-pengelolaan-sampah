<?php

namespace App\Data\Xendit\Common\Customer;

use Spatie\LaravelData\Data;

class CustomerIndividualDetailData extends Data
{
    public function __construct(
        public string $given_names,
        public ?string $surname,
    ) {}

    public static function rules(): array
    {
        return [
            'given_names' => ['required', 'string', 'max:50'],
            'surname'     => ['nullable', 'string', 'max:50'],
        ];
    }

    public function toArray(): array
    {
        return array_filter([
            'given_names' => $this->given_names,
            'surname' => $this->surname,
        ], fn($value) => !is_null($value));
    }
}
