<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Json;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;


class ProvinceData extends Data
{
    public function __construct(
        #[Max(2)]
        public string $code,

        #[Max(255)]
        public string $name,

        #[Json]
        public ?array $meta,
    ) {}
}
