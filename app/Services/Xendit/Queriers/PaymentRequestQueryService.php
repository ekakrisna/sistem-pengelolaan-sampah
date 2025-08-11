<?php

namespace App\Services\Xendit\Queriers;

use App\Services\Xendit\Requests\BaseXenditPaymentRequestService;
use Xendit\XenditSdkException;

class PaymentRequestQueryService extends BaseXenditPaymentRequestService
{
    /**
     * Ambil Payment Request dari Xendit berdasarkan ID (mis. "pr-xxxx").
     *
     * @param  string      $paymentRequestId
     * @param  string|null $forUserId        (opsional) override sub-account id
     * @return array
     */
    public function getById(string $paymentRequestId, ?string $forUserId = null): array
    {
        $forUserId = $forUserId ?: $this->defaultForUserId;

        try {
            $result = $this->api->getPaymentRequestByID($paymentRequestId, $forUserId);
            return json_decode(json_encode($result), true);
        } catch (XenditSdkException $e) {
            throw new \RuntimeException("Xendit payment request failed: {$e->getMessage()}");
        }
    }

    /**
     * Ambil semua Payment Request dari Xendit.
     *
     * @param  string|null $forUserId        (opsional) override sub-account id
     * @return array
     */
    public function getAll(?string $forUserId = null): array
    {
        $forUserId = $forUserId ?: $this->defaultForUserId;

        try {
            $result = $this->api->getAllPaymentRequests($forUserId);
            return json_decode(json_encode($result), true);
        } catch (XenditSdkException $e) {
            throw new \RuntimeException("Xendit payment request failed: {$e->getMessage()}");
        }
    }
}
