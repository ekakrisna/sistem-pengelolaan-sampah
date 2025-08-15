<?php

namespace App\Data\Xendit\Pay;

use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Illuminate\Validation\Rule;
use App\Data\Xendit\Pay\Common\ItemData;
use App\Enums\Xendit\Common\CaptureMethod;
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

        public float $request_amount,              // required, min 0 (sesuai spec)
        #[WithCast(EnumCast::class)]
        public ?CaptureMethod $capture_method = CaptureMethod::AUTOMATIC,

        public string $channel_code,               // required
        /** @var array<string,mixed> */
        public array $channel_properties,          // required (berbeda per channel)

        public ?string $description = null,        // optional
        public ?array $metadata = null,            // optional (k/v)
        /** @var ItemData[]|null */
        public ?array $items = null,               // optional array of items
    ) {}

    public static function rules(): array
    {
        return [
            'reference_id'     => ['required', 'string', 'min:1', 'max:255'],

            'country'          => ['required', Rule::in(Country::values())],
            'currency'         => ['required', Rule::in(Currency::values())],
            'request_amount'    => ['required', 'numeric', 'min:0'],

            'capture_method'   => ['nullable', Rule::in(CaptureMethod::values())],

            'channel_code'     => ['required', 'string', 'max:50'],
            'channel_properties' => ['required', 'array'],

            'description'      => ['nullable', 'string', 'min:1', 'max:1000'],
            'metadata'         => ['nullable', 'array'],

            'items'            => ['nullable', 'array'],
            'items.*'          => ['array'], // biar Spatie Data bisa cast ke ItemData
        ];
    }

    /** payload final ke Xendit */
    public function toPayload(): array
    {
        return array_filter([
            'reference_id'      => $this->reference_id,
            'type'              => 'PAY', // fixed
            'country'           => $this->country->value,
            'currency'          => $this->currency->value,
            'request_amount'    => $this->request_amount,
            'capture_method'    => $this->capture_method?->value ?? CaptureMethod::AUTOMATIC->value,
            'channel_code'      => $this->channel_code,
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
