<?php

namespace App\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;

class WasteTypeData extends Data
{
    public ?int $id;

    #[Max(191)]
    public string $name;
    public ?string $description;

    public int $admin_id;
    public ?Carbon $created_at;
    public ?Carbon $updated_at;

    public ?UserData $admin;
}
