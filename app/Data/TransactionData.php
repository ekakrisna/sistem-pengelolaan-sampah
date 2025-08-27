<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Attributes\Validation\Enum;
use App\Enums\TransactionEnum;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Date;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Attributes\Validation\Json;


class TransactionData extends Data
{

    public function __construct(
        public ?int $id,

        public int $customer_id,
        #[Max(191), Unique('transactions', 'number')]
        public ?string $number,
        #[Enum(TransactionEnum::class)]
        public TransactionEnum $status,
        #[Numeric]
        public int $subtotal,
        #[Numeric]
        public int $discount_amount,
        #[Numeric]
        public int $tax_amount,
        #[Numeric]
        public int $total,
        #[Max(8)]
        public string $currency,
        #[Date]
        public ?Carbon $due_at,
        #[Date]
        public ?Carbon $expires_at,

        public ?string $description,
        #[Json]
        public ?array $meta,

        #[Date]
        public ?Carbon $created_at,

        #[Date]
        public ?Carbon $updated_at,
        #[Date]
        public ?Carbon $deleted_at,

    ) {}


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
