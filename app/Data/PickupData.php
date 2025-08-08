<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Enum;
use App\Enums\PickupEnum;
use App\Models\PickupSchedule;
use App\Models\User;
use Spatie\LaravelData\Attributes\Validation\Date;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Max;

class PickupData extends Data
{
    public ?int $id;
    public int $pickup_schedule_id;

    public ?int $customer_id;

    public ?int $petugas_id;
    #[Enum(PickupEnum::class)]
    public PickupEnum $status;

    #[Max(500)]
    public ?string $note;

    #[Date]
    public ?Carbon $created_at;
    #[Date]
    public ?Carbon $updated_at;
    #[Date]
    public ?Carbon $deleted_at;

    public ?PickupSchedule $schedule;

    public ?User $customer;
    public ?User $petugas;
}
