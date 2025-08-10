<?php

namespace App\Services\Xendit\Methods;

use App\Services\Xendit\Methods\BaseXenditPaymentService;

class DirectDebitPHSubsequentService extends BaseXenditPaymentService
{
    /**
     * @param string|null $referenceId
     * @param int    $amount
     * @param string $paymentMethodId  contoh: pm-xxxx (hasil account linking)
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
        $referenceId = $this->ensureReferenceId($referenceId, 'DDPH');

        $payload = [
            'reference_id' => $referenceId,
            'amount' => $amount,
            'currency' => 'PHP',            // PH direct debit
            'payment_method_id' => $paymentMethodId,
            'metadata' => $metadata,
        ];

        return $this->send($payload, $idempotencyKey, $forUserId, $withSplitRuleId);
    }
}
