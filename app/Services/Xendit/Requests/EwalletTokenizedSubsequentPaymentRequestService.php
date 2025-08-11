<?php

namespace App\Services\Xendit\Requests;

use App\Services\Xendit\Requests\BaseXenditPaymentRequestService;

class EwalletTokenizedSubsequentPaymentRequestService extends BaseXenditPaymentRequestService
{
    /**
     * @param string|null $referenceId
     * @param int    $amount
     * @param string $paymentMethodId  contoh: pm-xxxx (hasil tokenization/linking)
     * @param array|null $metadata
     * @param string|null $idempotencyKey
     * @param string|null $forUserId
     * @param string|null $withSplitRuleId
     */
    public function create(
        ?string $referenceId,
        int $amount,
        string $paymentMethodId,
        ?array $metadata = null,
        ?string $idempotencyKey = null,
        ?string $forUserId = null,
        ?string $withSplitRuleId = null
    ): array {
        $referenceId = $this->ensureReferenceId($referenceId, 'EWTKN');

        $payload = [
            'reference_id' => $referenceId,
            'amount' => $amount,
            'currency' => $this->defaultCurrency,
            'payment_method_id' => $paymentMethodId,
            'metadata' => $metadata,
        ];

        return $this->send($payload, $idempotencyKey, $forUserId, $withSplitRuleId);
    }
}
