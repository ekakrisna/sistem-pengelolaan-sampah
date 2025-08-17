<?php

namespace App\Data\Xendit\Common\Customer;

use Spatie\LaravelData\Data;

class CustomerIndividualDetailData extends Data
{
    public function __construct(
        public string $given_names,
        public ?string $surname = null,
    ) {}

    public static function rules(): array
    {
        return [
            'given_names' => ['required', 'string', 'max:50'],
            'surname'     => ['nullable', 'string', 'max:50'],
        ];
    }
}
