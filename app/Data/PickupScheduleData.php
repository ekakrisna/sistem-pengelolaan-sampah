<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Enum;
use App\Enums\PickupScheduleEnum;
use App\Models\User;
use App\Models\Village;
use App\Models\WasteType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Date;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Spatie\LaravelData\Attributes\Validation\After;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Regex;

class PickupScheduleData extends Data
{
    public ?int $id;

    public int $waste_type_id;
    #[Enum(PickupScheduleEnum::class)]
    public PickupScheduleEnum $day_of_week;

    #[Regex('/^\d{2}:\d{2}(:\d{2})?$/')]
    public string $start_pickup_time;

    #[Regex('/^\d{2}:\d{2}(:\d{2})?$/'), After('start_pickup_time')]
    public string $end_pickup_time;

    #[Max(10), Exists('villages', 'code')]
    public string $code_village;

    #[Exists('users', 'id')]
    public int $admin_id;

    #[Date]
    public ?Carbon $created_at;
    #[Date]
    public ?Carbon $updated_at;

    #[Date]
    public ?Carbon $deleted_at;

    public ?WasteType $wasteType;

    public ?User $admin;

    public ?Collection $pickups;

    public ?Village $village;
}
