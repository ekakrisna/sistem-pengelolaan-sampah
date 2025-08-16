<?php

namespace App\Data\Xendit\Pay;

use App\Data\Xendit\Common\ItemData;
use App\Data\Xendit\Common\TokenChannelPropsData;
use App\Enums\Xendit\Common\CaptureMethod;
use App\Enums\Xendit\Common\Country;
use App\Enums\Xendit\Common\Currency;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class PaymentsApiPayWithTokenData extends Data
{
    public function __construct(
        public string $reference_id,
        public string $payment_token_id,
        public float $request_amount,
        public ?string $description = null,
        public ?array $metadata = null,
        #[WithCast(EnumCast::class)]
        public Country $country = Country::ID,
        #[WithCast(EnumCast::class)]
        public Currency $currency = Currency::IDR,
        #[WithCast(EnumCast::class)]
        public ?CaptureMethod $capture_method = CaptureMethod::AUTOMATIC,
        public ?TokenChannelPropsData $channel_properties,
        /** @var DataCollection<ItemData>|null */
        #[DataCollectionOf(ItemData::class)]
        public ?DataCollection $items = null,
    ) {
        parent::__construct(
            reference_id: $reference_id,
            request_amount: $request_amount,
            metadata: $metadata,
            country: $country,
            currency: $currency
        );
    }

    public static function rules(): array
    {
        return array_merge(parent::rules(), [
            'request_amount'     => ['required', 'numeric', 'min:0'],
            'payment_token_id'   => ['required', 'string', 'max:255'],
            'capture_method'     => ['nullable', Rule::in(CaptureMethod::values())],
            'channel_properties' => ['nullable', 'array'],
            'description'        => ['nullable', 'string', 'min:1', 'max:1000'],
            'items'              => ['nullable', 'array'],
            'items.*'            => ['array'],
        ]);
    }

    public function toPayload(): array
    {
        return array_filter([
            'reference_id'       => $this->reference_id,
            'type'               => 'PAY',
            'country'            => $this->country->value,
            'currency'           => $this->currency->value,
            'request_amount'     => $this->request_amount,
            'payment_token_id'   => $this->payment_token_id,
            'capture_method'     => $this->capture_method?->value ?? CaptureMethod::AUTOMATIC->value,
            'channel_properties' => $this->channel_properties?->toArray(),
            'description'        => $this->description,
            'metadata'           => $this->metadata,
            'items'              => $this->items?->toArray(),
        ], static fn($v) => $v !== null && $v !== '');
    }

    public static function make(
        string $referenceId,
        float $amount,
        string $paymentTokenId,
        ?string $successUrl = null,
        ?string $failureUrl = null,
        ?string $description = null,
        ?array $metadata = null,
        Country $country = Country::ID,
        Currency $currency = Currency::IDR,
        ?CaptureMethod $captureMethod = null,
        ?ItemData $items = null
    ): self {
        return self::from([
            'reference_id'       => $referenceId,
            'request_amount'     => $amount,
            'payment_token_id'   => $paymentTokenId,
            'channel_properties' => (new TokenChannelPropsData($successUrl, $failureUrl))->toArray(),
            'description'        => $description,
            'metadata'           => $metadata,
            'country'            => $country->value,
            'currency'           => $currency->value,
            'capture_method'     => ($captureMethod ?? CaptureMethod::AUTOMATIC)->value,
            'items'              => $items,
        ]);
    }
}
