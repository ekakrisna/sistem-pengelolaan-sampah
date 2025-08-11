<?php
// app/Data/PaymentMethod/CreatePaymentMethodData.php
namespace App\Data\PaymentMethod;

use App\Enums\CardOnFileType;
use App\Enums\PaymentMethodReusability;
use App\Enums\PaymentMethodType;
use Carbon\CarbonInterface;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class CreatePaymentMethodData extends Data
{
    #[WithCast(EnumCast::class)]
    public PaymentMethodType $type;

    #[WithCast(EnumCast::class)]
    public PaymentMethodReusability $reusability;

    // Opsional: country di PM Parameters (beberapa channel butuh)
    public ?string $country;

    /** Gunakan salah satu: customer_id atau customer (inline) */
    public ?string $customer_id;
    public ?CustomerInlineData $customer;

    // ===== Common (opsional)
    public ?string $reference_id;        // bila mau referensikan
    public ?string $description;
    public ?array  $metadata;
    public ?BillingInformationData $billing_information;
    public ?string $for_user_id;

    // ===== EWALLET =====
    public ?string $channel_code;        // OVO|SHOPEEPAY|DANA|GOPAY|LINKAJA
    public ?string $success_return_url;
    public ?string $failure_return_url;
    public ?string $cancel_return_url;
    public ?string $pending_return_url;

    // ===== CARD =====
    public ?string $currency;            // CardParameters.currency (required untuk CARD)
    public ?bool   $skip_three_d_secure; // linking phase (MULTIPLE_USE)
    #[WithCast(EnumCast::class)]
    public ?CardOnFileType $cardonfile_type;
    #[WithCast(DateTimeInterfaceCast::class, format: DATE_ATOM)]
    public ?CarbonInterface $expires_at;
    public ?array  $installment_configuration;
    public ?string $merchant_id_tag;
    public ?CardInformationData $card_information; // ⚠️ PCI only

    // ===== DIRECT DEBIT =====
    public ?array $channel_properties;   // spesifik bank (biarkan generic)

    public static function rules(): array
    {
        return [
            'type'        => ['required', Rule::in(array_column(PaymentMethodType::cases(), 'value'))],
            'reusability' => ['required', Rule::in(array_column(PaymentMethodReusability::cases(), 'value'))],
            'country'     => ['nullable', 'string', 'size:2'],

            // customer linkage
            'customer_id' => ['nullable', 'string'],
            'customer'    => ['nullable', 'array'],

            // common
            'reference_id' => ['nullable', 'string', 'max:191'],
            'description'  => ['nullable', 'string', 'max:500'],
            'metadata'     => ['nullable', 'array'],
            'billing_information' => ['nullable', 'array'],
            'for_user_id'  => ['nullable', 'string'],

            // ===== EWALLET rules =====
            'channel_code' => Rule::when(
                fn($i) => ($i['type'] ?? null) === 'EWALLET',
                ['required', 'string', 'in:OVO,SHOPEEPAY,DANA,GOPAY,LINKAJA']
            ),
            'success_return_url' => ['nullable', 'url'],
            'failure_return_url' => ['nullable', 'url'],
            'cancel_return_url'  => ['nullable', 'url'],
            'pending_return_url' => ['nullable', 'url'],

            // ===== CARD rules =====
            'currency' => Rule::when(
                fn($i) => ($i['type'] ?? null) === 'CARD',
                ['required', 'string']
            ),
            'skip_three_d_secure' => ['nullable', 'boolean'],
            'cardonfile_type'     => ['nullable', Rule::in(array_column(CardOnFileType::cases(), 'value'))],
            'expires_at'          => ['nullable', 'date'],
            'installment_configuration' => ['nullable', 'array'],
            'merchant_id_tag'     => ['nullable', 'string', 'max:100'],

            // ⚠️ PCI only (jika diisi, semua field penting harus valid)
            'card_information'            => ['nullable', 'array'],
            'card_information.card_number' => ['nullable', 'string', 'min:12', 'max:19'],
            'card_information.expiry_month' => ['nullable', 'string', 'regex:/^(0[1-9]|1[0-2])$/'],
            'card_information.expiry_year' => ['nullable', 'string', 'regex:/^(\d{2}|\d{4})$/'],
            'card_information.cardholder_name' => ['nullable', 'string', 'max:191'],
            'card_information.cvv'        => ['nullable', 'string', 'min:3', 'max:4'],

            // ===== DIRECT DEBIT =====
            'channel_properties' => Rule::when(
                fn($i) => ($i['type'] ?? null) === 'DIRECT_DEBIT',
                ['nullable', 'array'] // properti tergantung bank; validasi di layer service bila perlu
            ),
        ];
    }

    /**
     * Isi default ringan (opsional)
     */
    public function withDefaults(
        string $defaultCurrency = 'IDR',
        string $defaultCountry = 'ID',
        PaymentMethodReusability $defaultReusability = PaymentMethodReusability::MULTIPLE_USE
    ): self {
        $clone = clone $this;
        $clone->reusability ??= $defaultReusability;
        $clone->country     ??= $defaultCountry;
        if ($this->type === PaymentMethodType::CARD) {
            $clone->currency ??= $defaultCurrency;
        }
        return $clone;
    }
}
