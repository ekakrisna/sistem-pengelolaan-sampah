<?php

namespace App\Data\SplitRule;

use Spatie\LaravelData\Data;

class RouteData extends Data
{
    public function __construct(
        public ?float $flat_amount,
        public ?float $percent_amount,
        public string $currency,
        public string $destination_account_id,
        public string $reference_id,
    ) {}
}
