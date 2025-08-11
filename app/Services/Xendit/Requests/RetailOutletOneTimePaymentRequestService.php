<?php

namespace App\Services\Xendit\Requests;

use Carbon\Carbon;

class RetailOutletOneTimePaymentRequestService extends BaseXenditPaymentRequestService
{
    /**
     * Create one-time Retail Outlet payment code (e.g., ALFAMART / INDOMARET)
     *
     * @param  string|null             $referenceId
     * @param  int                     $amount                 IDR integer
     * @param  string                  $channelCode            'ALFAMART' | 'INDOMARET'
     * @param  string                  $payerName              Nama yang ditunjukkan di kasir
     * @param  \DateTimeInterface|null $expiresAt              (opsional) default +24 jam UTC
     * @param  string|null             $paymentCode            (opsional) custom 6-char code
     */
    public function create(
        ?string $referenceId,
        int $amount,
        string $channelCode,
        string $payerName,
        ?\DateTimeInterface $expiresAt = null,
        ?string $paymentCode = null,
        ?array $metadata = null,
        ?string $idempotencyKey = null,
        ?string $forUserId = null,
        ?string $withSplitRuleId = null
    ): array {
        $referenceId = $this->ensureReferenceId($referenceId, 'RO');
        $channelCode = strtoupper(trim($channelCode));

        // default expiry: +N jam dari config, kirim dalam UTC ISO8601
        $hours = (int) config('services.xendit.expiry_hours', 24);

        $expiry = $expiresAt
            ? Carbon::instance(\DateTime::createFromInterface($expiresAt))->utc()
            : now('UTC')->addHours($hours);

        $channelProps = [
            'customer_name' => $payerName,
            'expires_at' => $expiry->toIso8601String(),
        ];
        if (!empty($paymentCode)) {
            $channelProps['payment_code'] = strtoupper($paymentCode);
        }

        $payload = [
            'reference_id' => $referenceId,
            'amount'       => $amount,
            'currency'     => $this->defaultCurrency,
            'country'      => $this->defaultCountry,
            'payment_method' => [
                'type'        => 'OVER_THE_COUNTER',
                'reusability' => 'ONE_TIME_USE',
                'over_the_counter' => [
                    'channel_code'       => $channelCode,
                    'channel_properties' => $channelProps,
                ],
            ],
            'metadata' => $metadata,
        ];


        return $this->send($payload, $idempotencyKey, $forUserId, $withSplitRuleId);
    }
}
