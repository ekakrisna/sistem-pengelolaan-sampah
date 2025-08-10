<?php

namespace App\Services\Xendit\Methods;

use App\Services\Xendit\Methods\BaseXenditPaymentService;

class EwalletRedirectOneTimeService extends BaseXenditPaymentService
{
    /**
     * @param string|null $referenceId
     * @param int    $amount               in smallest unit (IDR normal integer)
     * @param string $channelCode          e.g. SHOPEEPAY | OVO | DANA | LINKAJA | GOPAY (sesuaikan yang tersedia)
     * @param string $successReturnUrl
     * @param array|null $metadata
     * @param string|null $idempotencyKey
     * @param string|null $forUserId
     * @param string|null $withSplitRuleId
     */
    public function create(
        ?string $referenceId,
        int $amount,
        string $channelCode,
        string $successReturnUrl,
        ?array $metadata = null,
        ?string $idempotencyKey = null,
        ?string $forUserId = null,
        ?string $withSplitRuleId = null
    ): array {
        $referenceId = $this->ensureReferenceId($referenceId, 'EWALLET');

        $payload = [
            'reference_id' => $referenceId,
            'amount' => $amount,
            'currency' => $this->defaultCurrency,
            'country'  => $this->defaultCountry,
            'payment_method' => [
                'type' => 'EWALLET',
                'reusability' => 'ONE_TIME_USE',
                'ewallet' => [
                    'channel_code' => $channelCode,
                    'channel_properties' => [
                        'success_return_url' => $successReturnUrl,
                    ],
                ],
            ],
            'metadata' => $metadata,
        ];

        return $this->send($payload, $idempotencyKey, $forUserId, $withSplitRuleId);
    }
}
