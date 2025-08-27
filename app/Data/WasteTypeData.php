<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Date;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

class WasteTypeData extends Data
{
    public function __construct(
        public ?int $id,
        public int $admin_id,
        #[Max(191)]
        public string $name,
        public ?string $description,
        #[Date]
        public ?Carbon $created_at,
        #[Date]
        public ?Carbon $updated_at,
        #[Date]
        public ?Carbon $deleted_at,

        public ?UserData $user,

        #[DataCollectionOf(PickupFeeData::class)]
        public ?DataCollection $pickup_fees,

        #[DataCollectionOf(PickupScheduleData::class)]
        public ?DataCollection $pickup_schedules,

    ) {}

    public static function paginatedResponse(LengthAwarePaginator $paginator): array
    {
        $items = method_exists($paginator, 'items') ? $paginator->items() : $paginator;
        return [
            'waste_types' => self::collect($items),
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
