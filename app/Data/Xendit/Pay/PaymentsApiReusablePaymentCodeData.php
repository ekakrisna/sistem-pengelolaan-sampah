<?php

namespace App\Data\Xendit\Pay;

use App\Data\Xendit\Common\ItemData;
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

class PaymentsApiReusablePaymentCodeData extends Data
{
    public function __construct(
        public string $reference_id,
        #[WithCast(EnumCast::class)]
        public Country $country,
        #[WithCast(EnumCast::class)]
        public Currency $currency,
        public ?float $request_amount = null,
        #[WithCast(EnumCast::class)]
        public ?CaptureMethod $capture_method = CaptureMethod::AUTOMATIC,
        public ChannelCode $channel_code,
        public array $channel_properties,
        public ?string $description = null,
        public ?array $metadata = null,
        /** @var DataCollection<ItemData>|null */
        #[DataCollectionOf(ItemData::class)]
        public ?DataCollection $items = null,
    ) {}

    public static function rules(): array
    {
        return [
            'reference_id'       => ['required', 'string', 'min:1', 'max:255'],
            'country'            => ['required', Rule::in(Country::values())],
            'currency'           => ['required', Rule::in(Currency::values())],
            'request_amount'     => ['nullable', 'numeric', 'min:0'],
            'capture_method'     => ['nullable', Rule::in(CaptureMethod::values())],
            'channel_code'       => ['required', Rule::in(ChannelCode::values())],
            'channel_properties' => ['required', 'array'],
            'description'        => ['nullable', 'string', 'min:1', 'max:1000'],
            'metadata'           => ['nullable', 'array'],
            'items'              => ['nullable', 'array'],
            'items.*'            => ['array'],
        ];
    }

    /** payload final ke Xendit */
    public function toPayload(): array
    {
        return array_filter([
            'reference_id'       => $this->reference_id,
            'type'               => 'REUSABLE_PAYMENT_CODE',
            'country'            => $this->country->value,
            'currency'           => $this->currency->value,
            'request_amount'     => $this->request_amount,
            'capture_method'     => $this->capture_method?->value ?? CaptureMethod::AUTOMATIC->value,
            'channel_code'       => $this->channel_code,
            'channel_properties' => $this->channel_properties,
            'description'        => $this->description,
            'metadata'           => $this->metadata,
            'items'              => $this->items?->toArray(),
        ], static fn($v) => $v !== null && $v !== '');
    }

    /** Helper: cepat bikin “no amount” */
    public static function noAmount(
        string $referenceId,
        Country $country,
        Currency $currency,
        string $channelCode,
        array $channelProps,
        ?string $description = null,
        ?array $metadata = null,
        ?array $items = null // array of ItemData|array
    ): self {
        return self::from([
            'reference_id'       => $referenceId,
            'country'            => $country->value,
            'currency'           => $currency->value,
            'request_amount'     => null,
            'capture_method'     => CaptureMethod::AUTOMATIC->value,
            'channel_code'       => $channelCode,
            'channel_properties' => $channelProps,
            'description'        => $description,
            'metadata'           => $metadata,
            'items'              => $items,
        ]);
    }

    /** Helper: cepat bikin “with amount” */
    public static function withAmount(
        string $referenceId,
        Country $country,
        Currency $currency,
        float $amount,
        string $channelCode,
        array $channelProps,
        ?string $description = null,
        ?array $metadata = null,
        ?array $items = null
    ): self {
        return self::from([
            'reference_id'       => $referenceId,
            'country'            => $country->value,
            'currency'           => $currency->value,
            'request_amount'     => $amount,
            'capture_method'     => CaptureMethod::AUTOMATIC->value,
            'channel_code'       => $channelCode,
            'channel_properties' => $channelProps,
            'description'        => $description,
            'metadata'           => $metadata,
            'items'              => $items,
        ]);
    }
}
