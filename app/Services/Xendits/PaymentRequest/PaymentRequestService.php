<?php

namespace App\Services\Xendits\PaymentRequest;

use App\Data\Xendit\PaymentRequest\PaymentRequestListQueryData;
use App\Services\Xendits\Http\XenditService;

class PaymentRequestService extends XenditService
{

    public function getPaymentRequests(PaymentRequestListQueryData $query): array
    {
        $q = $query->toQuery();
        $qs = $this->buildQueryString($q);
        return $this->get('/payment_requests', $qs);
    }

    /**
     * Get Payment Request by ID (v3)
     */
    public function getById(string $paymentRequestId): array
    {
        return $this->get("/v3/payment_requests/{$paymentRequestId}");
    }

    /**
     * Cancel Payment Request by ID (v3)
     * 
     * @param string $paymentRequestId
     * @return array
     */
    public function cancel(string $paymentRequestId): array
    {
        return $this->post("/v3/payment_requests/{$paymentRequestId}/cancel");
    }

    /**
     * Simulate Payment Request by ID (v3)
     *
     * @param string $paymentRequestId
     * @param int|float $amount
     * @return array
     */
    public function simulate(string $paymentRequestId, int|float $amount): array
    {
        return $this->post("/v3/payment_requests/{$paymentRequestId}/simulate", [
            'amount' => $amount,
        ]);
    }
}
