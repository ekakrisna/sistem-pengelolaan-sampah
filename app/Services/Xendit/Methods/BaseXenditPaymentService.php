<?php

namespace App\Services\Xendit\Methods;

use Illuminate\Support\Str;
use Xendit\Configuration;
use Xendit\PaymentRequest\PaymentRequestApi;
use Xendit\PaymentRequest\PaymentRequestParameters;
use Xendit\XenditSdkException;

class BaseXenditPaymentService
{
    protected PaymentRequestApi $api;
    protected ?string $defaultForUserId;
    protected string $defaultCurrency;
    protected string $defaultCountry;

    public function __construct()
    {
        // Set API Key dari config
        Configuration::setXenditKey(config('services.xendit.api_key'));

        $this->api = new PaymentRequestApi();
        $this->defaultForUserId = config('services.xendit.for_user_id') ?: null;
        $this->defaultCurrency  = config('services.xendit.currency', 'IDR');
        $this->defaultCountry   = config('services.xendit.country', 'ID');
    }

    /** Kalau kosong, generate reference id dengan prefix yang kamu mau */
    protected function ensureReferenceId(?string $ref, string $prefix): string
    {
        $ref = trim((string) $ref);
        return $ref !== '' ? $ref : $this->generateExternalId($prefix);
    }

    /** Sesuai request-mu */
    protected function generateExternalId(string $prefix): string
    {
        return strtolower($prefix . '-' . now()->format('ymdhis') . '-' . uniqid());
    }

    protected function send(array $payload, ?string $idempotencyKey, ?string $forUserId, ?string $withSplitRuleId): array
    {
        $idempotencyKey = $idempotencyKey ?: (string) Str::uuid();
        $forUserId = $forUserId ?: $this->defaultForUserId;

        // Hapus key null agar payload bersih
        $payload = $this->arrayFilterNull($payload);

        try {
            $params = new PaymentRequestParameters($payload);
            $result = $this->api->createPaymentRequest($idempotencyKey, $forUserId, $withSplitRuleId, $params);
            return json_decode(json_encode($result), true);
        } catch (XenditSdkException $e) {
            throw new \RuntimeException("Xendit payment request failed: {$e->getMessage()}");
        }
    }

    private function arrayFilterNull(array $data): array
    {
        return array_map(function ($v) {
            if (is_array($v)) return $this->arrayFilterNull($v);
            return $v;
        }, array_filter($data, fn($v) => !is_null($v)));
    }

    protected function toArray(mixed $value): array
    {
        if (is_array($value)) return $value;
        return json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
}
