<?php

namespace App\Data;

use App\Enums\PickupFeeEnum;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Date;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\DataCollection;

class PickupFeeData extends Data
{
    public function __construct(
        #[Max(10)]
        public string $village_code,

        public int $waste_type_id,

        public int $admin_id,
        #[Numeric]
        public int $amount,

        public ?string $description,

        #[Enum(PickupFeeEnum::class)]
        public PickupFeeEnum $interval_unit,

        #[Numeric]
        public int $interval_count,

        public bool $is_active,

        #[Date]
        public ?Carbon $created_at,
        #[Date]
        public ?Carbon $updated_at,

        #[Date]
        public ?Carbon $deleted_at,

        public ?UserData $admin,

        public ?VillageData $village,

        public ?WasteTypeData $waste_type,

        #[DataCollectionOf(TransactionItemData::class)]
        public ?DataCollection $transaction_items,
    ) {}


    public static function paginatedResponse(LengthAwarePaginator $paginator): array
    {
        $items = method_exists($paginator, 'items') ? $paginator->items() : $paginator;
        return [
            'pickup_fees' => self::collect($items),
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
