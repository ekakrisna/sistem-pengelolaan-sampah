<?php

namespace App\Data\Xendit\Pay\Card;

use Spatie\LaravelData\Data;

class CardDetailsData extends Data
{
    public function __construct(
        public string $cvn,
        public string $card_number,
        public string $expiry_year,
        public string $expiry_month,
        public ?string $cardholder_first_name = null,
        public ?string $cardholder_last_name = null,
        public ?string $cardholder_email = null,
        public ?string $cardholder_phone_number = null,
    ) {}

    public static function rules(): array
    {
        return [
            'cvn'                     => ['required', 'string', 'min:3', 'max:4'],
            'card_number'             => ['required', 'string', 'min:12', 'max:19'],
            'expiry_year'             => ['required', 'string', 'size:4'],
            'expiry_month'            => ['required', 'string', 'size:2'],
            'cardholder_first_name'   => ['nullable', 'string', 'max:100'],
            'cardholder_last_name'    => ['nullable', 'string', 'max:100'],
            'cardholder_email'        => ['nullable', 'email', 'max:255'],
            'cardholder_phone_number' => ['nullable', 'string', 'max:30'],
        ];
    }
}
