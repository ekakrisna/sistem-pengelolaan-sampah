<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Json;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;


class DistrictData extends Data
{
    public function __construct(
        #[Max(5)]
        public string $code,

        #[Max(4)]
        public string $city_code,

        #[Max(255)]
        public string $name,

        #[Json]
        public ?array $meta,
    ) {}
}
