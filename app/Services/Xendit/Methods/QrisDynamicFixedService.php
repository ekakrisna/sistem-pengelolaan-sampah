<?php

namespace App\Services\Xendit\Methods;

use App\Services\Xendit\Methods\BaseXenditPaymentService;


class QrisDynamicFixedService extends BaseXenditPaymentService
{
    /**
     * @param string|null $referenceId
     * @param int    $amount
     * @param array|null $metadata
     * @param string|null $idempotencyKey
     * @param string|null $forUserId
     * @param string|null $withSplitRuleId
     */
    public function create(
        ?string $referenceId,
        int $amount,
        ?array $metadata = null,
        ?string $idempotencyKey = null,
        ?string $forUserId = null,
        ?string $withSplitRuleId = null
    ): array {
        $referenceId = $this->ensureReferenceId($referenceId, 'QRIS');

        $payload = [
            'reference_id' => $referenceId,
            'amount' => $amount,
            'currency' => $this->defaultCurrency,
            'payment_method' => [
                'type' => 'QR_CODE',
                'reusability' => 'ONE_TIME_USE',
                'qr_code' => [
                    'channel_code' => 'QRIS', // <- pakai quotes normal
                ],
            ],
            'metadata' => $metadata,
        ];

        return $this->send($payload, $idempotencyKey, $forUserId, $withSplitRuleId);
    }
}
