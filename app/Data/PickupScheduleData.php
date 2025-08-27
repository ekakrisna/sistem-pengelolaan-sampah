<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Enum;
use App\Enums\PickupScheduleEnum;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Date;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

class PickupScheduleData extends Data
{

    public function __construct(

        public ?int $id,
        public int $admin_id,

        public int $waste_type_id,

        #[Enum(PickupScheduleEnum::class)]
        public PickupScheduleEnum $day_of_week,

        public string $start_pickup_time,

        public string $end_pickup_time,
        #[Max(10)]
        public string $village_code,

        public int $quota,

        #[Date]
        public ?Carbon $created_at,

        #[Date]
        public ?Carbon $updated_at,

        #[Date]
        public ?Carbon $deleted_at,

        public ?UserData $admin,
        public ?WasteTypeData $waste_type,

        public ?VillageData $village,

        #[DataCollectionOf(PickupData::class)]
        public ?DataCollection $pickups,

        #[DataCollectionOf(TransactionItemData::class)]
        public ?DataCollection $transaction_items,
    ) {}

    public static function paginatedResponse(LengthAwarePaginator $paginator): array
    {
        $items = method_exists($paginator, 'items') ? $paginator->items() : $paginator;
        return [
            'pickup_schedules' => self::collect($items),
            'pagination' => [
                'total'         => $paginator->total(),
                'per_page'      => $paginator->perPage(),
                'current_page'  => $paginator->currentPage(),
                'last_page'     => $paginator->lastPage(),
                'from'          => $paginator->firstItem(),
                'to'            => $paginator->lastItem(),
            ],
        ];
    }
}
