<?php

namespace App\Services\Xendits\PaymentRequest;

use App\Data\Xendit\PaymentRequest\PaymentsApiPayData;
use App\Services\Xendits\Http\XenditService;

class PaymentPayService extends XenditService
{
    public function create(
        PaymentsApiPayData $data,
        string $idempotencyKey,
        ?string $forUserId = null,
        ?string $splitRuleId = null,
        ?array $query = []
    ): array {
        return $this->post(
            '/v3/payment_requests',
            $data->toPayload(),
            query: $query,
            forUserId: $forUserId,
            splitRuleId: $splitRuleId,
            idempotencyKey: $idempotencyKey
        );
    }

    public function payWithPresentToCustomer(PaymentsApiPayData $data): array
    {
        return $this->post('/v3/payment_requests', $data->toArray());
    }

    public function payWithRedirect(PaymentsApiPayData $data): array
    {
        return $this->post('/v3/payment_requests', $data->toArray());
    }

    public function payWithCard(PaymentsApiPayData $data): array
    {
        return $this->post('/v3/payment_requests', $data->toArray());
    }
}
