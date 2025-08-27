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

class TransactionItemData extends Data
{
    public ?int $id;
    public int $transaction_id;
    #[Enum(TransactionItemEnum::class)]
    public TransactionItemEnum $item_type;

    public ?int $user_address_id;

    public ?int $pickup_schedule_id;

    public ?int $pickup_fee_id;

    public ?int $pickup_id;
    #[Max(191)]
    public ?string $description;
    #[Numeric]
    public int $unit_amount;

    public int $qty;
    #[Numeric]
    public int $line_total;
    #[Json]
    public ?array $meta;
    #[Date]
    public ?Carbon $deleted_at;


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
