<?php

namespace App\Services\Xendit\Methods;

use Carbon\CarbonInterface;
use App\Services\Xendit\Methods\BaseXenditPaymentService;
use Carbon\Carbon;

class VirtualAccountFixedSingleUseService extends BaseXenditPaymentService
{
    /**
     * @param string|null $referenceId          reference transaksi kamu (Payment Request)
     * @param int    $amount
     * @param string $bankCode             e.g. BNI | BCA | BRI | MANDIRI | PERMATA | CIMB
     * @param string $customerName
     * @param CarbonInterface $expiresAtUtc   gunakan waktu UTC agar ISO8601Z valid
     * @param string|null $vaReferenceId   reference untuk VA di level payment_method
     * @param array|null $metadata
     * @param string|null $idempotencyKey
     * @param string|null $forUserId
     * @param string|null $withSplitRuleId
     */
    public function create(
        ?string $referenceId,
        int $amount,
        string $bankCode,
        string $customerName,
        ?\DateTimeInterface $expiresAt = null,
        ?string $vaReferenceId = null,
        ?array $metadata = null,
        ?string $idempotencyKey = null,
        ?string $forUserId = null,
        ?string $withSplitRuleId = null
    ): array {
        $referenceId = $this->ensureReferenceId($referenceId, 'VA');

        // default expiry: +N jam dari config, kirim dalam UTC ISO8601
        $hours = (int) config('services.xendit.expiry_hours', 24);

        $expiry = $expiresAt
            ? Carbon::instance(\DateTime::createFromInterface($expiresAt))->utc()
            : now('UTC')->addHours($hours);

        $payload = [
            'reference_id' => $referenceId,
            'currency' => $this->defaultCurrency,
            'amount' => $amount,
            'country' => $this->defaultCountry,
            'payment_method' => [
                'type' => 'VIRTUAL_ACCOUNT',
                'reusability' => 'ONE_TIME_USE',
                'reference_id' => $vaReferenceId, // opsional
                'virtual_account' => [
                    'channel_code' => strtoupper($bankCode),
                    'channel_properties' => [
                        'customer_name' => $customerName,
                        'expires_at' => $expiry->toIso8601String(), // contoh: 2025-08-10T03:00:00Z
                    ],
                ],
            ],
            'metadata' => $metadata,
        ];

        return $this->send($payload, $idempotencyKey, $forUserId, $withSplitRuleId);
    }
}
