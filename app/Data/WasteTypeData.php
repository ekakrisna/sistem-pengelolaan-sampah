<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;


class WasteTypeData extends Data
{
    public ?int $id;

    #[Max(191)]
    public string $name;

    public ?string $description;
}
