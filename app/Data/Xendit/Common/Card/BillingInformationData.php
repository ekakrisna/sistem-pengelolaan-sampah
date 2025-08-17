<?php

namespace App\Data\Xendit\Common\Card;

use App\Enums\Xendit\Common\Country;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

class BillingInformationData extends Data
{
    public function __construct(
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?string $email = null,
        public ?string $phone_number = null,
        public ?string $city = null,

        #[WithCast(EnumCast::class)]
        public ?Country $country = null,

        public ?string $postal_code = null,
        public ?string $street_line1 = null,
        public ?string $street_line2 = null,
        public ?string $province_state = null,
    ) {}

    public static function rules(): array
    {
        return [
            'first_name'    => ['nullable', 'string', 'max:100'],
            'last_name'     => ['nullable', 'string', 'max:100'],
            'email'         => ['nullable', 'email', 'max:255'],
            'phone_number'  => ['nullable', 'string', 'max:30'],
            'city'          => ['nullable', 'string', 'max:255'],
            'country'       => ['nullable', Rule::in(Country::values())],
            'postal_code'   => ['nullable', 'string', 'max:255'],
            'street_line1'  => ['nullable', 'string', 'max:255'],
            'street_line2'  => ['nullable', 'string', 'max:255'],
            'province_state' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toArray(): array
    {
        return array_filter([
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'email'         => $this->email,
            'phone_number'  => $this->phone_number,
            'city'          => $this->city,
            'country'       => $this->country?->value,
            'postal_code'   => $this->postal_code,
            'street_line1'  => $this->street_line1,
            'street_line2'  => $this->street_line2,
            'province_state' => $this->province_state,
        ], static fn($v) => $v !== null && $v !== '');
    }
}
