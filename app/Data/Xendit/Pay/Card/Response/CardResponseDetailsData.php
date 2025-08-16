<?php

namespace App\Data\Xendit\Pay\Card\Response;

use Spatie\LaravelData\Data;

class CardResponseDetailsData extends Data
{
    public function __construct(
        public ?string $masked_card_number = null,
        public ?string $cardholder_first_name = null,
        public ?string $cardholder_last_name = null,
        public ?string $cardholder_email = null,
        public ?string $cardholder_phone_number = null,
        public ?string $expiry_month = null,
        public ?string $expiry_year = null,
        public ?string $fingerprint = null,
        public ?string $type = null,     // CREDIT/DEBIT/...
        public ?string $network = null,  // VISA/MASTERCARD/...
        public ?string $country = null,  // issuing country (ISO-2)
        public ?string $issuer = null,   // bank name
    ) {}
}
