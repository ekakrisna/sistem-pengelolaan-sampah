<?php

namespace App\Data\Xendit\Pay;

use App\Data\Xendit\Common\Customer\CustomerData;
use App\Data\Xendit\Pay\Card\CardPayAndSaveChannelPropsData;
use App\Enums\Xendit\Common\CaptureMethod;
use App\Enums\Xendit\Common\ChannelCode;
use App\Enums\Xendit\Common\Country;
use App\Enums\Xendit\Common\Currency;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

class PaymentsApiPayAndSaveData extends Data
{
    public function __construct(
        public string $reference_id,
        public float $request_amount,
        public CustomerData $customer,
        public ChannelCode $channel_code,
        public CardPayAndSaveChannelPropsData $channel_properties,
        public ?string $description = null,
        public ?array $metadata = null,
        #[WithCast(EnumCast::class)]
        public Country $country = Country::ID,
        #[WithCast(EnumCast::class)]
        public Currency $currency = Currency::IDR,
        #[WithCast(EnumCast::class)]
        public ?CaptureMethod $capture_method = CaptureMethod::AUTOMATIC,
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
            'customer'           => ['required', 'array'],
            'channel_code'       => ['required', Rule::in(ChannelCode::values())],
            'channel_properties' => ['required', 'array'],
            'description'        => ['nullable', 'string', 'max:1000'],
            'capture_method'     => ['nullable', Rule::in(CaptureMethod::values())],
        ]);
    }

    public function toPayload(): array
    {
        return array_filter([
            'reference_id'       => $this->reference_id,
            'type'               => 'PAY_AND_SAVE',
            'country'            => $this->country->value,
            'currency'           => $this->currency->value,
            'request_amount'     => $this->request_amount,
            'capture_method'     => $this->capture_method?->value ?? CaptureMethod::AUTOMATIC->value,
            'channel_code'       => $this->channel_code->value,
            'customer'           => $this->customer->toArray(),
            'channel_properties' => $this->channel_properties->toArray(),
            'description'        => $this->description,
            'metadata'           => $this->metadata,
        ], static fn($v) => $v !== null && $v !== '');
    }
}
