<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Json;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;


class VillageData extends Data
{
    public function __construct(
        #[Max(11)]
        public string $code,

        #[Max(7)]
        public string $district_code,

        #[Max(255)]
        public string $name,

        #[Json]
        public ?array $meta,
    ) {}
}
