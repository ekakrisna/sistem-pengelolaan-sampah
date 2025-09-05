<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Enum;
use App\Enums\TransactionItemEnum;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Json;
use Spatie\LaravelData\Attributes\Validation\Date;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

class TransactionItemData extends Data
{
    public function __construct(
        public ?int $id,
        public int $transaction_id,
        #[Enum(TransactionItemEnum::class)]
        public TransactionItemEnum $item_type,

        public ?int $user_address_id,

        public ?int $pickup_schedule_id,

        public ?int $pickup_fee_id,

        public ?int $pickup_id,
        #[Max(191)]
        public ?string $description,
        #[Numeric]
        public int $unit_amount,

        public int $qty,
        #[Numeric]
        public int $line_total,

        #[Date]
        public ?Carbon $current_period_start,
        #[Date]
        public ?Carbon $current_period_end,

        #[Json]
        public ?array $meta,

        #[Date]
        public ?Carbon $created_at,
        #[Date]
        public ?Carbon $updated_at,

        #[Date]
        public ?Carbon $deleted_at,

        public ?PickupFeeData $pickup_fee,
        public ?PickupScheduleData $pickup_schedule,

        public ?UserAddressData $user_address,

        public ?TransactionData $transaction
    ) {}



    public static function paginatedResponse(LengthAwarePaginator $paginator): array
    {
        $items = method_exists($paginator, 'items') ? $paginator->items() : $paginator;
        return [
            'transaction_items' => self::collect($items),
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
