<?php

namespace App\Services\Xendit\Methods;

namespace App\Services\Xendit\Methods;

use Xendit\Configuration;
use Xendit\PaymentMethod\PaymentMethodApi;
use Xendit\PaymentMethod\PaymentMethodParameters;

abstract class BaseXenditPaymentMethodService
{
    protected string $defaultCurrency;
    protected string $defaultCountry;
    protected ?string $defaultForUserId;
    protected PaymentMethodApi $pmApi;

    public function __construct(?string $apiKey = null)
    {
        $key = $apiKey ?: config('services.xendit.api_key');
        if (!$key) throw new \RuntimeException('Xendit API key is not configured.');

        $this->defaultCurrency  = config('services.xendit.currency', 'IDR');
        $this->defaultCountry   = config('services.xendit.country',  'ID');
        $this->defaultForUserId = config('services.xendit.for_user_id');

        Configuration::setXenditKey($key);
        $this->pmApi = new PaymentMethodApi();
    }

    protected function send(array $payload, ?string $forUserId = null): array
    {
        $payload = $this->clean($payload);

        try {
            $params = new PaymentMethodParameters($payload);
            $resp   = $this->pmApi->createPaymentMethod($forUserId ?: $this->defaultForUserId, $params);

            return json_decode(json_encode($resp), true) ?: [];
        } catch (\Xendit\XenditSdkException $e) {
            $msg = 'Xendit createPaymentMethod failed: ' . $e->getMessage();
            throw new \RuntimeException($msg, previous: $e);
        }
    }

    protected function clean(mixed $data): mixed
    {
        if (!is_array($data)) return $data;

        $out = [];
        foreach ($data as $k => $v) {
            $v = $this->clean($v);

            $isEmptyArray = is_array($v) && $v === [];
            if ($v === null) continue;

            if ($isEmptyArray && str_ends_with((string) $k, 'channel_properties')) {
                $out[$k] = (object)[];
                continue;
            }
            if ($isEmptyArray) continue;

            $out[$k] = $v;
        }
        return $out;
    }
}
