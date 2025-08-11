<?php

namespace App\Services\Xendit\Queriers;

use Xendit\Configuration;
use Xendit\PaymentMethod\PaymentMethodApi;

class PaymentMethodQueryService
{
    private PaymentMethodApi $api;
    private ?string $defaultForUserId;

    public function __construct(?string $apiKey = null)
    {
        $key = $apiKey ?: config('services.xendit.api_key');
        if (!$key) throw new \RuntimeException('Xendit API key is not configured.');
        Configuration::setXenditKey($key);

        $this->api = new PaymentMethodApi();
        $this->defaultForUserId = config('services.xendit.for_user_id');
    }

    public function getById(string $paymentMethodId, ?string $forUserId = null): array
    {
        try {
            $res = $this->api->getPaymentMethodByID($paymentMethodId, $forUserId ?: $this->defaultForUserId);
            return json_decode(json_encode($res), true) ?: [];
        } catch (\Xendit\XenditSdkException $e) {
            $msg = 'Xendit getPaymentMethodByID failed: ' . $e->getMessage();
            throw new \RuntimeException($msg, previous: $e);
        }
    }
}
