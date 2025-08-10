<?php

namespace App\Data;

use App\Enums\PaymentChannelCategory;
use Carbon\CarbonInterface;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class CreatePaymentData extends Data
{
    // Kategori/jenis channel
    #[WithCast(EnumCast::class)]
    public PaymentChannelCategory $channel_category;

    // Common
    public ?string $reference_id;
    public int $amount;
    public ?string $currency;
    public ?string $country;
    public ?array $metadata;
    public ?string $idempotency_key;
    public ?string $for_user_id;
    public ?string $with_split_rule_id;

    // E-Wallet (redirect)
    public ?string $ewallet_channel_code;
    public ?string $success_return_url;
    public ?string $ewallet_mobile_number;

    // Tokenized / Direct Debit
    public ?string $payment_method_id;

    // Virtual Account
    public ?string $bank_code;
    public ?string $customer_name;

    // Retail Outlet
    public ?string $channel_code;
    public ?string $payer_name;
    public ?string $payment_code;

    // #[WithCast(Iso8601ToCarbonCaster::class)]
    public ?CarbonInterface $expires_at;

    public ?string $va_reference_id;

    // --- Transaction-related (lokal)
    public ?int $customer_id;              // untuk tabel payments.transaction kamu
    public ?int $pickup_id;                // kalau transaksi terhubung pickup
    public ?string $transaction_description;

    /** @var DataCollection<TransactionItemData>|null */
    #[DataCollectionOf(TransactionItemData::class)]
    public ?DataCollection $items;

    public static function rules(): array
    {
        return [
            // payment
            'channel_category'   => ['required', Rule::in(array_column(PaymentChannelCategory::cases(), 'value'))],
            'reference_id'       => ['nullable', 'string'],
            'amount'             => ['required', 'integer', 'min:1'],
            'currency'           => ['nullable', 'string'],
            'country'            => ['nullable', 'string'],
            'metadata'           => ['nullable', 'array'],
            'idempotency_key'    => ['nullable', 'string'],
            'for_user_id'        => ['nullable', 'string'],
            'with_split_rule_id' => ['nullable', 'string'],

            // ewallet (redirect-only)
            'payment_method_id'    => ['nullable', 'string'],
            'ewallet_channel_code' => ['nullable', 'string', 'exclude_unless:channel_category,EWALLET', 'required_without:payment_method_id'],
            'success_return_url'   => ['nullable', 'url', 'exclude_unless:channel_category,EWALLET', 'required_without_all:payment_method_id,ewallet_mobile_number'],
            'ewallet_mobile_number' => ['nullable', 'string', 'exclude_unless:channel_category,EWALLET', 'required_if:ewallet_channel_code,OVO'],

            'expires_at' => ['nullable', 'date', 'exclude_unless:channel_category,VIRTUAL_ACCOUNT,RETAIL_OUTLET'],

            // VA and Retail Outlet
            'bank_code'      => ['nullable', 'string', 'required_if:channel_category,VIRTUAL_ACCOUNT'],
            'customer_name'  => ['nullable', 'string', 'required_if:channel_category,VIRTUAL_ACCOUNT'],
            'va_reference_id' => ['nullable', 'string'],

            // Retail Outlet
            'channel_code'   => ['nullable', 'string', 'required_if:channel_category,RETAIL_OUTLET'],
            'payer_name'     => ['nullable', 'string', 'required_if:channel_category,RETAIL_OUTLET'],
            'payment_code'   => ['nullable', 'string', 'required_if:channel_category,RETAIL_OUTLET'],

            // lokal (opsional)
            'customer_id'             => ['nullable', 'integer'],
            'pickup_id'               => ['nullable', 'integer'],
            'transaction_description' => ['nullable', 'string'],

            // items
            'items'              => ['nullable', 'array'],
            'items.*.pickup_fee_id' => ['nullable', 'integer'],
            'items.*.description'   => ['nullable', 'string'],
            'items.*.unit_amount'   => ['required_with:items', 'numeric', 'min:0'],
            'items.*.qty'           => ['required_with:items', 'integer', 'min:1'],
            'items.*.meta'          => ['nullable', 'array'],
        ];
    }

    public function withDefaults(string $defaultCurrency = 'IDR', string $defaultCountry = 'ID'): self
    {
        $clone = clone $this;
        if (!$clone->currency) {
            $clone->currency = $this->channel_category === PaymentChannelCategory::DIRECT_DEBIT_PH ? 'PHP' : $defaultCurrency;
        }
        if (!$clone->country) {
            $clone->country = $defaultCountry;
        }
        return $clone;
    }

    /** Hitung total dari items (kalau ada). */
    public function totalFromItems(): ?float
    {
        if (!$this->items) return null;
        return $this->items->toCollection()->reduce(
            fn($sum, TransactionItemData $i) => $sum + $i->lineTotal(),
            0.0
        );
    }
}
