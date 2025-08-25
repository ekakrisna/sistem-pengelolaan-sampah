<?php

namespace App\Data;

use App\Enums\StatusTransactionEnum;
use App\Models\Payment;
use App\Models\Pickup;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Date;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\DataCollection;

class TransactionData extends Data
{
    public function __construct(
        public ?int $id,
        public ?int $pickup_id,

        #[Enum(StatusTransactionEnum::class)]
        public ?StatusTransactionEnum $status,

        #[Numeric]
        public float|int $total,

        public ?string $description,
        public ?Carbon $created_at,
        public ?Carbon $updated_at,

        #[Date]
        public ?Carbon $deleted_at,

        public ?Payment $payment,
        public ?Pickup $pickup,

        /** @var DataCollection<TransactionItemData>|null */
        #[DataCollectionOf(TransactionItemData::class)]
        public ?DataCollection $items,
    ) {}

    /**
     * Bentuk respons paginated dengan format custom.
     */
    public static function paginatedResponse(LengthAwarePaginator $paginator): array
    {
        $items = method_exists($paginator, 'items') ? $paginator->items() : $paginator;

        return [
            'transactions' => self::collect($items),
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
