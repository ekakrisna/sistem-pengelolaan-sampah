<?php

namespace App\Data\Xendit\Pay\Redirect\Response;

use App\Data\Xendit\Common\ActionData;
use App\Data\Xendit\Common\RedirectChannelPropsData;
use App\Enums\Xendit\Common\CaptureMethod;
use App\Enums\Xendit\Common\ChannelCode;
use App\Enums\Xendit\Common\Country;
use App\Enums\Xendit\Common\Currency;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class PaymentRequestRedirectPayResponseData extends Data
{
    public function __construct(
        public ?string $business_id,
        public string $reference_id,
        public string $payment_request_id,
        public string $type,
        public Country $country,           // enum
        public Currency $currency,         // enum
        public float $request_amount,
        public ?CaptureMethod $capture_method, // enum|null
        public ChannelCode $channel_code,  // enum
        public RedirectChannelPropsData $channel_properties, // Data anak
        /** @var DataCollection<RedirectActionData>|null */
        public ?DataCollection $actions,   // koleksi Data anak
        public string $status,
        public ?string $description = null,
        public ?array $metadata = null,
        public ?string $created = null,
        public ?string $updated = null,
    ) {}

    public static function rules(): array
    {
        $countryIn   = implode(',', Country::values());
        $currencyIn  = implode(',', Currency::values());
        $captureIn   = implode(',', CaptureMethod::values());
        $channelIn   = implode(',', ChannelCode::values());

        return [
            'business_id'        => ['nullable', 'string'],
            'reference_id'       => ['required', 'string', 'max:255'],
            'payment_request_id' => ['required', 'string', 'max:255'],
            'type'               => ['required', 'string', 'in:PAY'],

            'country'            => ['required', "in:$countryIn"],
            'currency'           => ['required', "in:$currencyIn"],
            'request_amount'     => ['required', 'numeric', 'min:0'],
            'capture_method'     => ['nullable', "in:$captureIn"],

            'channel_code'       => ['required', "in:$channelIn"],
            'channel_properties' => ['required', 'array'],

            'actions'            => ['nullable', 'array'],
            'status'             => ['required', 'string', 'max:100'],
            'description'        => ['nullable', 'string', 'max:1000'],
            'metadata'           => ['nullable', 'array'],
            'created'            => ['nullable', 'string', 'max:50'],
            'updated'            => ['nullable', 'string', 'max:50'],
        ];
    }

    /** Helper untuk memetakan dari array response Xendit */
    public static function fromXendit(array $p): self
    {
        $actions = null;
        if (!empty($p['actions'])) {
            $actions = ActionData::collect($p['actions'], DataCollection::class);
        }

        return new self(
            reference_id: $p['reference_id'],
            payment_request_id: $p['payment_request_id'],
            business_id: $p['business_id'] ?? null,
            type: $p['type'],
            country: Country::from($p['country']),
            currency: Currency::from($p['currency']),
            request_amount: (float) $p['request_amount'],
            capture_method: isset($p['capture_method']) ? CaptureMethod::from($p['capture_method']) : null,
            channel_code: ChannelCode::from($p['channel_code']),
            channel_properties: new RedirectChannelPropsData(
                expires_at: $p['channel_properties']['expires_at'] ?? null,
                success_return_url: $p['channel_properties']['success_return_url'] ?? null,
                failure_return_url: $p['channel_properties']['failure_return_url'] ?? null,
                cancel_return_url: $p['channel_properties']['cancel_return_url'] ?? null,
            ),
            actions: $actions,
            status: $p['status'],
            description: $p['description'] ?? null,
            metadata: $p['metadata'] ?? null,
            created: $p['created'] ?? null,
            updated: $p['updated'] ?? null,
        );
    }
}
