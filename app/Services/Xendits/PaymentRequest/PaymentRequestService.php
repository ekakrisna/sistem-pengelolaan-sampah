<?php

namespace App\Services\Xendits\PaymentRequest;

use App\Services\Xendits\Http\XenditService;

class PaymentRequestService extends XenditService
{
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
     * Get Payment by ID (v3)
     *
     * @param string $paymentId ex: py-bb322184-4bae-42ce-bacf-0f84049e046e
     * @return array
     */
    public function getByPaymentId(string $paymentId): array
    {
        return $this->get("/v3/payments/{$paymentId}");
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
