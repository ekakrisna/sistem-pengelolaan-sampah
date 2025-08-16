<?php

namespace App\Data\Xendit\Pay;

use App\Data\Xendit\Common\ItemData;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use App\Enums\Xendit\Common\CaptureMethod;
use App\Enums\Xendit\Common\ChannelCode;
use App\Enums\Xendit\Common\Country;
use App\Enums\Xendit\Common\Currency;

class PaymentsApiPayData extends Data
{
    public function __construct(
        public string $reference_id,
        #[WithCast(EnumCast::class)]
        public Country $country,
        #[WithCast(EnumCast::class)]
        public Currency $currency,
        public float $request_amount,
        #[WithCast(EnumCast::class)]
        public ?CaptureMethod $capture_method = CaptureMethod::AUTOMATIC,
        public ChannelCode $channel_code,
        /** @var array<string,mixed> */
        public array $channel_properties,
        public ?string $description = null,
        public ?array $metadata = null,
        /** @var ItemData[]|null */
        public ?array $items = null,
    ) {}

    public static function rules(): array
    {
        $countryIn   = implode(',', Country::values());
        $currencyIn  = implode(',', Currency::values());
        $captureIn   = implode(',', CaptureMethod::values());
        $channelIn   = implode(',', ChannelCode::values());

        return [
            'reference_id'     => ['required', 'string', 'min:1', 'max:255'],
            'country'          => ['required', "in:$countryIn"],
            'currency'         => ['required', "in:$currencyIn"],
            'request_amount'    => ['required', 'numeric', 'min:0'],
            'capture_method'   => ['nullable', "in:$captureIn"],
            'channel_code'       => ['required', "in:$channelIn"],
            'channel_properties' => ['required', 'array'],
            'description'      => ['nullable', 'string', 'min:1', 'max:1000'],
            'metadata'         => ['nullable', 'array'],
            'items'            => ['nullable', 'array'],
            'items.*'          => ['array'],
        ];
    }

    public function toPayload(): array
    {
        return array_filter([
            'reference_id'      => $this->reference_id,
            'type'              => 'PAY',
            'country'           => $this->country->value,
            'currency'          => $this->currency->value,
            'request_amount'    => $this->request_amount,
            'capture_method'    => $this->capture_method?->value ?? CaptureMethod::AUTOMATIC->value,
            'channel_code'      => $this->channel_code->value,
            'channel_properties' => $this->channel_properties,
            'description'       => $this->description,
            'metadata'          => $this->metadata,
            'items'             => $this->items ? array_map(
                fn(ItemData $i) => $i->toArray(),
                $this->items
            ) : null,
        ], static fn($v) => $v !== null && $v !== '');
    }
}
