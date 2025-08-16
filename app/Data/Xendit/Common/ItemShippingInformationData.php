<?php

namespace App\Data\Xendit\Common;

use Spatie\LaravelData\Data;
use Illuminate\Validation\Rule;
use App\Enums\Xendit\Common\Country;

class ItemShippingInformationData extends Data
{
    public function __construct(
        public ?Country $country = null,
        public ?string $street_line1 = null,
        public ?string $street_line2 = null,
        public ?string $city = null,
        public ?string $province_state = null,
        public ?string $postal_code = null,
    ) {}

    public static function rules(): array
    {
        return [
            'country'        => ['nullable', Rule::in(Country::values())],
            'street_line1'   => ['nullable', 'string', 'min:1', 'max:255'],
            'street_line2'   => ['nullable', 'string', 'min:1', 'max:255'],
            'city'           => ['nullable', 'string', 'min:1', 'max:255'],
            'province_state' => ['nullable', 'string', 'min:1', 'max:255'],
            'postal_code'    => ['nullable', 'string', 'min:1', 'max:255'],
        ];
    }

    public function toArray(): array
    {
        return array_filter([
            'country'        => $this->country?->value,
            'street_line1'   => $this->street_line1,
            'street_line2'   => $this->street_line2,
            'city'           => $this->city,
            'province_state' => $this->province_state,
            'postal_code'    => $this->postal_code,
        ], static fn($v) => $v !== null && $v !== '');
    }
}
