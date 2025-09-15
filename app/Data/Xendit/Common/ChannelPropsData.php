<?php

namespace App\Data\Xendit\Common;

use App\Data\Xendit\Common\Card\BillingInformationData;
use App\Data\Xendit\Common\Card\CardDetailsData;
use App\Data\Xendit\Common\Card\RecurringConfigurationData;
use App\Enums\Xendit\Common\ChannelCode;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\LaravelData\Data;

class ChannelPropsData extends Data
{

    private static function defaultReturnUrls(): array
    {
        $version = config('app.api.version', 'v1');

        return [
            'success_return_url' => "/api/{$version}/success/",
            'failure_return_url' => "/api/{$version}/failure/",
            'pending_return_url' => "/api/{$version}/pending/",
            'cancel_return_url'  => "/api/{$version}/cancel/",
        ];
    }

    /** Keys allowed by ChannelPropsData */
    private const ALLOWED_KEYS = [
        'expires_at',
        'success_return_url',
        'failure_return_url',
        'cancel_return_url',
        'account_mobile_number',
        'display_name',
        'mid_label',
        'card_details',
        'skip_three_ds',
        'card_on_file_type',
        'billing_information',
        'statement_descriptor',
        'recurring_configuration',
        'payer_name',
        'virtual_account_number',
        // optionally allow 'account_email' if you use it upstream then remove before DTO (not in ChannelPropsData)
        'account_email',
    ];

    /** Minimal required keys per channel (adjust as needed for your flows) */
    private const REQUIRED_BY_CHANNEL = [
        // Retail Outlets (example)
        'ALFAMART' => ['payer_name', 'expires_at'],
        'INDOMARET' => ['payer_name', 'expires_at'],

        // E-Wallets (example set)
        'OVO'       => ['account_mobile_number'],
        'DANA'      => [], // can be email or mobile depending on flow; keep flexible
        'LINKAJA'   => [],
        'SHOPEEPAY' => [],
        'GCASH'     => [],
        'GRABPAY'   => [],

        // Virtual Account (examples)
        'BCA_VA'    => ['expires_at'],
        'BNI_VA'    => ['expires_at'],
        'BRI_VA'    => ['expires_at'],
        'MANDIRI_VA' => ['expires_at'],

        // Cards
        'CARD'      => [], // you may enforce e.g. card_details or skip_three_ds depending on your flow
    ];

    public function __construct(
        public ?string $expires_at,
        public ?string $success_return_url,
        public ?string $failure_return_url,
        public ?string $cancel_return_url,
        public ?string $account_mobile_number,
        public ?string $display_name,
        public ?string $mid_label,
        public ?CardDetailsData $card_details = null,
        public ?bool $skip_three_ds,
        public ?string $card_on_file_type,
        public ?BillingInformationData $billing_information = null,
        public ?string $statement_descriptor,
        public ?RecurringConfigurationData $recurring_configuration = null,
        public ?string $payer_name,
        public ?string $virtual_account_number,
        public ?string $account_email
    ) {}

    public static function rules(): array
    {
        return [
            'expires_at'            => ['nullable', 'string', 'max:50'],
            'success_return_url'    => ['nullable', 'url'],
            'failure_return_url'    => ['nullable', 'url'],
            'cancel_return_url'     => ['nullable', 'url'],
            'account_mobile_number' => ['nullable', 'string', 'max:20'],
            'display_name'          => ['nullable', 'string', 'max:100'],
            'mid_label'             => ['nullable', 'string', 'max:255'],
            'card_details'          => ['nullable', 'array'],
            'skip_three_ds'         => ['nullable', 'boolean'],
            'card_on_file_type'     => ['nullable', 'string', 'max:100'],
            'billing_information'   => ['nullable', 'array'],
            'statement_descriptor'  => ['nullable', 'string', 'max:255'],
            'recurring_configuration' => ['nullable', 'array'],
            'payer_name'            => ['nullable', 'string', 'max:100'],
            'virtual_account_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function toArray(): array
    {
        return array_filter([
            'expires_at'              => $this->expires_at,
            'success_return_url'      => $this->success_return_url,
            'failure_return_url'      => $this->failure_return_url,
            'cancel_return_url'       => $this->cancel_return_url,
            'account_mobile_number'   => $this->account_mobile_number,
            'display_name'            => $this->display_name,
            'mid_label'               => $this->mid_label,
            'card_details'            => $this->card_details?->toArray(),
            'skip_three_ds'           => $this->skip_three_ds,
            'card_on_file_type'       => $this->card_on_file_type,
            'billing_information'     => $this->billing_information?->toArray(),
            'statement_descriptor'    => $this->statement_descriptor,
            'recurring_configuration' => $this->recurring_configuration?->toArray(),
            'payer_name'              => $this->payer_name,
            'virtual_account_number'   => $this->virtual_account_number,
        ], fn($value) => !is_null($value));
    }

    /**
     * Build + validate + normalize channel_properties for a given channel.
     *
     * @param ChannelCode $channel
     * @param array       $incoming channel_properties from request
     * @param Transaction $trx     used to backfill name/phone/email if missing
     * @param CarbonImmutable|null $defaultExpiry if you already computed a default
     *
     * @return array Clean array for DTO -> toArray()
     */
    public static function buildForChannel(
        ChannelCode $channel,
        array $incoming,
        Transaction $trx,
        ?CarbonImmutable $defaultExpiry = null
    ): array {
        // 1) Keep only allowed keys
        $props = [];
        foreach (self::ALLOWED_KEYS as $k) {
            if (array_key_exists($k, $incoming)) {
                $props[$k] = $incoming[$k];
            }
        }

        // 2) Backfill defaults from transaction/customer if missing
        $props['display_name'] = self::firstNonEmpty(
            $props['display_name'] ?? null,
            optional($trx->customer)->name
        );

        // E-Wallets often need mobile number; we can prefill if absent
        if (self::isEwallet($channel)) {
            $props['account_mobile_number'] = self::firstNonEmpty(
                self::normalizePhone($props['account_mobile_number'] ?? null),
                self::normalizePhone(optional($trx->customer)->phone)
            );
            // Optional: some wallets accept email; you may pass to Xendit even if not in ChannelPropsData
            $props['account_email'] = self::firstNonEmpty(
                $props['account_email'] ?? null,
                optional($trx->customer)->email
            );
        }

        // Retail Outlet defaults: payer_name from customer if absent
        if (self::isRetailOutlet($channel)) {
            $props['payer_name'] = self::firstNonEmpty(
                $props['payer_name'] ?? null,
                optional($trx->customer)->name
            );
        }

        // 3) Expires_at: prefer incoming; else use provided default
        if (!empty($props['expires_at'])) {
            $props['expires_at'] = self::toIso8601($props['expires_at']);
        } elseif ($defaultExpiry) {
            $props['expires_at'] = $defaultExpiry->toIso8601String();
        }

        // 4) Normalize URLs/booleans/strings
        foreach (['success_return_url', 'failure_return_url', 'cancel_return_url', 'pending_return_url'] as $urlKey) {
            if (!empty($props[$urlKey]) && !self::isValidUrl($props[$urlKey])) {
                throw new InvalidArgumentException("Invalid URL for {$urlKey}");
            }
        }

        foreach (self::defaultReturnUrls() as $key => $path) {
            if (empty($props[$key])) {
                $base = rtrim(config('app.url', env('APP_URL', 'http://localhost:8000')), '/');
                $props[$key] = $base . $path . Str::lower($trx->number);
            }
        }

        if (array_key_exists('skip_three_ds', $props)) {
            $props['skip_three_ds'] = self::toBoolOrNull($props['skip_three_ds']);
        }

        foreach (
            [
                'display_name',
                'mid_label',
                'card_on_file_type',
                'statement_descriptor',
                'payer_name',
                'virtual_account_number',
                'account_email'
            ] as $sk
        ) {
            if (isset($props[$sk]) && is_string($props[$sk])) {
                $props[$sk] = trim($props[$sk]);
            }
        }

        // 5) Validate per channel
        $missing = self::missingRequired($channel, $props);
        if (!empty($missing)) {
            throw new InvalidArgumentException(
                'Missing required channel_properties: ' . implode(', ', $missing)
            );
        }

        // 7) Finally, drop nulls so your DTO’s toArray stays clean
        return array_filter($props, static fn($v) => !is_null($v));
    }

    /*** Helpers ***/
    private static function firstNonEmpty(...$vals): ?string
    {
        foreach ($vals as $v) {
            if (is_string($v) && trim($v) !== '') return trim($v);
        }
        return null;
    }

    private static function normalizePhone(?string $raw): ?string
    {
        if ($raw === null) return null;
        // keep only digits and leading +
        return preg_replace('/[^0-9+]/', '', $raw);
    }

    private static function toIso8601(string $val): string
    {
        return CarbonImmutable::parse($val)->toIso8601String();
    }

    private static function isValidUrl(string $url): bool
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }

    private static function toBoolOrNull($v): ?bool
    {
        if ($v === null) return null;
        if (is_bool($v)) return $v;
        $s = strtolower((string)$v);
        if (in_array($s, ['1', 'true', 'yes'], true)) return true;
        if (in_array($s, ['0', 'false', 'no'], true)) return false;
        return null;
    }

    private static function missingRequired(ChannelCode $channel, array $props): array
    {
        $key = $channel->value;
        $required = self::REQUIRED_BY_CHANNEL[$key] ?? [];

        // Some families share base requirement rules
        if (empty($required)) {
            if (self::isEwallet($channel)) {
                // Minimal for your flow; adjust if your product needs email instead
                $required = ['account_mobile_number'];
            } elseif (self::isRetailOutlet($channel)) {
                $required = ['payer_name', 'expires_at'];
            }
        }

        $missing = [];
        foreach ($required as $rk) {
            if (!array_key_exists($rk, $props) || $props[$rk] === null || $props[$rk] === '') {
                $missing[] = $rk;
            }
        }
        return $missing;
    }

    private static function isEwallet(ChannelCode $channel): bool
    {
        return in_array($channel->value, [
            'OVO',
            'DANA',
            'LINKAJA',
            'SHOPEEPAY',
            'GCASH',
            'GRABPAY',
            'PAYMAYA'
        ], true);
    }

    private static function isRetailOutlet(ChannelCode $channel): bool
    {
        return in_array($channel->value, ['ALFAMART', 'INDOMARET'], true);
    }
}
