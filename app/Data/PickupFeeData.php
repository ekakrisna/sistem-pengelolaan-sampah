<?php

namespace App\Data;

use App\Models\Village;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Date;
use Carbon\Carbon;

class PickupFeeData extends Data
{
    public ?int $id;

    #[Max(10)]
    public string $village_code;

    public int $waste_type_id;

    public int $admin_id;
    #[Numeric]
    public int $amount;
    #[Date]
    public ?Carbon $created_at;

    #[Date]
    public ?Carbon $updated_at;

    #[Date]
    public ?Carbon $deleted_at;

    public ?WasteTypeData $wasteType;
    public ?UserData $admin;
    public ?Village $village;
}
