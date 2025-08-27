<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Enum;
use App\Enums\PaymentEnum;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Attributes\Validation\Json;
use Spatie\LaravelData\Attributes\Validation\Date;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentData extends Data
{
    public ?int $id;
    public int $transaction_id;

    public int $customer_id;
    #[Numeric]
    public int $amount;
    #[Max(8)]
    public string $currency;
    #[Enum(PaymentEnum::class)]
    public PaymentEnum $status;
    #[Max(191)]
    public ?string $channel;
    #[Max(191)]
    public ?string $method_code;
    #[Max(191)]
    public ?string $reference_id;
    #[Max(191), Unique('payments', 'idempotency_key')]
    public ?string $idempotency_key;
    #[Max(191)]
    public ?string $xendit_account_id;
    #[Max(191), Unique('payments', 'xendit_payment_request_id')]
    public ?string $xendit_payment_request_id;
    #[Max(191), Unique('payments', 'xendit_charge_id')]
    public ?string $xendit_charge_id;
    #[Max(191), Unique('payments', 'xendit_invoice_id')]
    public ?string $xendit_invoice_id;
    #[Json]
    public ?array $va_numbers;

    public ?string $qris_qr_string;
    #[Max(191)]
    public ?string $checkout_url;
    #[Json]
    public ?array $ewallet_info;
    #[Date]
    public ?Carbon $expires_at;
    #[Date]
    public ?Carbon $paid_at;
    #[Max(191)]
    public ?string $failure_code;
    #[Max(191)]
    public ?string $failure_message;
    #[Json]
    public ?array $xendit_data;
    #[Date]
    public ?Carbon $deleted_at;

    public static function paginatedResponse(LengthAwarePaginator $paginator): array
    {
        $items = method_exists($paginator, 'items') ? $paginator->items() : $paginator;
        return [
            'payments' => self::collect($items),
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
