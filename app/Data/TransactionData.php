<?php

namespace App\Data;

use App\Models\Payment;
use App\Models\Pickup;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Date;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

class TransactionData extends Data
{
    public ?int $id;
    public int $payment_id;

    public ?int $pickup_id;
    #[Numeric]
    public int $total;

    public ?string $description;
    public ?Carbon $created_at;
    public ?Carbon $updated_at;

    #[Date]
    public ?Carbon $deleted_at;

    public ?Payment $payment;
    public ?Pickup $pickup;
    #[DataCollectionOf(TransactionItemData::class)]
    public ?DataCollection $items;
}
