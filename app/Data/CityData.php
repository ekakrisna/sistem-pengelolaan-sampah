<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Json;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;


class CityData extends Data
{
    public function __construct(
        #[Max(5)]
        public string $code,

        #[Max(2)]
        public string $province_code,

        #[Max(255)]
        public string $name,

        #[Json]
        public ?array $meta,
    ) {}
}
