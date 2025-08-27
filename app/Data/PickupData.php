<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Enum;
use App\Enums\PickupEnum;
use Spatie\LaravelData\Attributes\Validation\Date;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

class PickupData extends Data
{
    public function __construct(
        public ?int $id,
        public int $pickup_schedule_id,

        public int $customer_id,

        public ?int $petugas_id,
        #[Enum(PickupEnum::class)]
        public PickupEnum $status,

        public ?string $note,

        #[Date]
        public ?Carbon $created_at,

        #[Date]
        public ?Carbon $updated_at,

        #[Date]
        public ?Carbon $deleted_at,

        public ?UserData $customer,

        public ?UserData $petugas,

        public ?PickupScheduleData $pickup_schedule,

        #[DataCollectionOf(TransactionItemData::class)]
        public ?DataCollection $transaction_items,
    ) {}


    public static function paginatedResponse(LengthAwarePaginator $paginator): array
    {
        $items = method_exists($paginator, 'items') ? $paginator->items() : $paginator;
        return [
            'pickups' => self::collect($items),
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
