<?php

namespace App\Data\Xendit\Pay;

use App\Data\Xendit\Common\ChannelPropsData;
use App\Data\Xendit\Common\Customer\CustomerData;
use App\Data\Xendit\Common\ItemData;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use App\Enums\Xendit\Common\CaptureMethod;
use App\Enums\Xendit\Common\ChannelCode;
use App\Enums\Xendit\Common\Country;
use App\Enums\Xendit\Common\Currency;
use App\Enums\Xendit\Common\CustomerType;

class PaymentsApiPayData extends Data
{
    public function __construct(
        public string $reference_id,

        #[WithCast(EnumCast::class)]
        public Country $country,

        #[WithCast(EnumCast::class)]
        public Currency $currency,

        public float $request_amount,

        public ?CustomerData $customer,

        #[WithCast(EnumCast::class)]
        public ?CaptureMethod $capture_method = CaptureMethod::AUTOMATIC,

        #[WithCast(EnumCast::class)]
        public ChannelCode $channel_code,

        public ChannelPropsData $channel_properties,
        public ?string $description = null,
        public ?array $metadata = null,
        public ?ItemData $items = null,
    ) {}

    public static function rules(): array
    {
        $countryIn  = implode(',', Country::values());
        $currencyIn = implode(',', Currency::values());
        $captureIn  = implode(',', CaptureMethod::values());
        $channelIn  = implode(',', ChannelCode::values());
        $ovo        = ChannelCode::OVO->value;
        $customerType = implode(',', CustomerType::values());

        return [
            'reference_id'         => ['required', 'string', 'min:1', 'max:255'],
            'country'              => ['required', "in:$countryIn"],
            'currency'             => ['required', "in:$currencyIn"],
            'request_amount'       => ['required', 'numeric', 'min:0'],

            // customer boleh tidak ada
            'customer'                                     => ['nullable', 'array'],

            // jika "customer" ada, semua field di bawahnya jadi wajib
            'customer.reference_id'                        => ['required_with:customer', 'string', 'min:1', 'max:255'],
            'customer.type'                                => ['required_with:customer', 'string', "in:$customerType"],
            'customer.mobile_number'                       => ['required_with:customer', 'string', 'min:1', 'max:50'],
            'customer.email'                               => ['nullable', 'email', 'max:255'],

            // individual_detail wajib kalau customer ada
            'customer.individual_detail'                   => ['required_with:customer', 'array'],
            'customer.individual_detail.given_names'       => ['required_with:customer.individual_detail', 'string', 'max:50'],
            'customer.individual_detail.surname'           => ['required_with:customer.individual_detail', 'string', 'max:50'],

            'capture_method'       => ['nullable', "in:$captureIn"],
            'channel_code'         => ['required', "in:$channelIn"],
            'channel_properties'   => ['required', 'array'],

            "channel_properties.account_mobile_number" => ["required_if:channel_code,$ovo", 'string', 'max:20'],

            'description'          => ['nullable', 'string', 'min:1', 'max:1000'],
            'metadata'             => ['nullable', 'array'],
            'items'                => ['nullable', 'array'],
            'items.*'              => ['array'],
        ];
    }

    public function toPayload(): array
    {
        return array_filter([
            'reference_id'       => $this->reference_id,
            'type'               => 'PAY',
            'country'            => $this->country->value,
            'currency'           => $this->currency->value,
            'request_amount'     => $this->request_amount,
            'customer'           => $this->customer?->toArray(),
            'capture_method'     => $this->capture_method?->value ?? CaptureMethod::AUTOMATIC->value,
            'channel_code'       => $this->channel_code->value,
            'channel_properties' => $this->channel_properties->toArray(), // <- pastikan toArray
            'description'        => $this->description,
            'metadata'           => $this->metadata,
            'items'              => $this->items?->toArray(),
        ], static fn($v) => $v !== null && $v !== '');
    }
}
