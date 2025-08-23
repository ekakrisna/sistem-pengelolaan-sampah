<?php

namespace App\Services\Xendits\Payment;

use App\Services\Xendits\Http\XenditService;

class PaymentService extends XenditService
{
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
     * Cancel Payment by ID (v3)
     *
     * @param string $paymentId ex: py-bb322184-4bae-42ce-bacf-0f84049e046e
     * @return array
     */
    public function cancelPayment(string $paymentId): array
    {
        return $this->post("/v3/payments/{$paymentId}/cancel");
    }

    /**
     * Capture Payment by ID (v3)
     *
     * @param string $paymentId ex: py-bb322184-4bae-42ce-bacf-0f84049e046e
     * @return array
     */
    public function capturePayment(string $paymentId): array
    {
        return $this->post("/v3/payments/{$paymentId}/capture");
    }
}
