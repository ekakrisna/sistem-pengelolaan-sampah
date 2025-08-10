<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Enum;
use App\Enums\PaymentEnum;
use App\Models\User;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Date;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;

class PaymentData extends Data
{
    public ?int $id;
    public int $customer_id;
    #[Numeric]
    public int $amount;
    #[Enum(PaymentEnum::class)]
    public PaymentEnum $status;
    #[Max(191)]
    public ?string $payment_method;
    #[Max(191)]
    public ?string $external_id;
    #[Max(191)]
    public ?string $invoice_url;
    public ?array $xendit_data;
    #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s')]
    public ?Carbon $paid_at;
    #[Date]
    public ?Carbon $created_at;
    #[Date]
    public ?Carbon $updated_at;
    #[Date]
    public ?Carbon $deleted_at;

    public ?User $customer;
    // #[DataCollectionOf(TransactionData::class)]
    public ?TransactionData $transaction;
}
