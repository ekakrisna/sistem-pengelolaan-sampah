<?php

namespace App\Data\Xendit\Pay\Present\Response;

use App\Data\Xendit\Common\ActionData;
use App\Enums\Xendit\Common\CaptureMethod;
use App\Enums\Xendit\Common\ChannelCode;
use App\Enums\Xendit\Common\Country;
use App\Enums\Xendit\Common\Currency;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class PaymentRequestPresentToCustomerResponseData extends Data
{
    public function __construct(
        public ?string $business_id,
        public string $reference_id,
        public string $payment_request_id,
        public string $type, // "PAY"

        #[WithCast(EnumCast::class)]
        public Country $country,

        #[WithCast(EnumCast::class)]
        public Currency $currency,

        public float $request_amount,

        #[WithCast(EnumCast::class)]
        public ?CaptureMethod $capture_method = null,

        #[WithCast(EnumCast::class)]
        public ChannelCode $channel_code, // e.g. BRI_VIRTUAL_ACCOUNT

        // khusus contoh VA; untuk channel lain buat DTO props yang sesuai
        public VaPresentChannelPropsData $channel_properties,

        /** @var DataCollection<ActionData>|null */
        #[DataCollectionOf(ActionData::class)]
        public ?DataCollection $actions = null,

        public string $status,            // e.g. REQUIRES_ACTION
        public ?string $description = null,
        public ?array $metadata = null,
        public ?string $created = null,   // ISO8601
        public ?string $updated = null,   // ISO8601
    ) {}

    public static function rules(): array
    {
        return [
            'business_id'        => ['nullable', 'string'],
            'reference_id'       => ['required', 'string', 'max:255'],
            'payment_request_id' => ['required', 'string', 'max:255'],
            'type'               => ['required', 'string', 'in:PAY'],

            'country'            => ['required', Rule::in(Country::values())],
            'currency'           => ['required', Rule::in(Currency::values())],
            'request_amount'     => ['required', 'numeric', 'min:0'],
            'capture_method'     => ['nullable', Rule::in(CaptureMethod::values())],

            'channel_code'       => ['required', Rule::in(ChannelCode::values())],
            'channel_properties' => ['required', 'array'],

            'actions'            => ['nullable', 'array'],
            'status'             => ['required', 'string', 'max:100'],
            'description'        => ['nullable', 'string', 'max:1000'],
            'metadata'           => ['nullable', 'array'],
            'created'            => ['nullable', 'string', 'max:50'],
            'updated'            => ['nullable', 'string', 'max:50'],
        ];
    }

    /** Helper: mapping langsung dari array response Xendit */
    public static function fromXendit(array $payload): self
    {
        return self::from($payload);
    }
}
