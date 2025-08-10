<?php

namespace App\Data;

use App\Casts\Iso8601ToCarbonCaster;
use App\Enums\PaymentChannelCategory;
use Carbon\CarbonInterface;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

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

    // Tokenized / Direct Debit
    public ?string $payment_method_id;

    // Virtual Account
    public ?string $bank_code;
    public ?string $customer_name;

    // #[WithCast(Iso8601ToCarbonCaster::class)]
    public ?CarbonInterface $expires_at;

    public ?string $va_reference_id;

    public static function rules(): array
    {
        return [
            'channel_category'   => ['required', Rule::in(array_column(PaymentChannelCategory::cases(), 'value'))],
            'reference_id'       => ['nullable', 'string'],
            'amount'             => ['required', 'integer', 'min:1'],
            'currency'           => ['nullable', 'string'],
            'country'            => ['nullable', 'string'],
            'metadata'           => ['nullable', 'array'],
            'idempotency_key'    => ['nullable', 'string'],
            'for_user_id'        => ['nullable', 'string'],
            'with_split_rule_id' => ['nullable', 'string'],

            // Tokenized / Direct Debit
            'payment_method_id'  => ['nullable', 'string'],

            // EWALLET (redirect): hanya divalidasi kalau channel_category = EWALLET
            'ewallet_channel_code' => [
                'nullable',
                'string',
                'exclude_unless:channel_category,EWALLET',
                // Wajib kalau EWALLET & payment_method_id kosong (redirect case)
                'required_without:payment_method_id',
            ],
            'success_return_url' => [
                'nullable',
                'url',
                'exclude_unless:channel_category,EWALLET',
                // Wajib kalau EWALLET redirect (karena pakai channel_code)
                'required_with:ewallet_channel_code',
            ],

            // QRIS — tidak ada field wajib tambahan

            // VIRTUAL ACCOUNT
            'bank_code'      => ['nullable', 'string', 'required_if:channel_category,VIRTUAL_ACCOUNT'],
            'customer_name'  => ['nullable', 'string', 'required_if:channel_category,VIRTUAL_ACCOUNT'],
            'expires_at'     => ['nullable', 'date',   'required_if:channel_category,VIRTUAL_ACCOUNT'],
            'va_reference_id' => ['nullable', 'string'],
        ];
    }


    /**
     * Helper: normalize default currency/country bila kosong.
     * (Opsional – kamu bisa lakukan ini di Manager juga)
     */
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
}
