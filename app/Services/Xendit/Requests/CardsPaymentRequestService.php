<?php

namespace App\Services\Xendit\Requests;

use App\Services\Xendit\Requests\BaseXenditPaymentRequestService;

class CardsPaymentRequestService extends BaseXenditPaymentRequestService
{
    /**
     * Buat Payment Request kartu.
     *
     * Path sesuai docs:
     * - Gunakan payment_method_id (saved card), ATAU
     * - payment_method = ['type'=>'CARD','reusability'=>..., 'card'=>['card_information'=>['one_time_token'|'token_id'=>...]]]
     * - Properti 3DS/COF/OTP/cvv di-isi pada top-level channel_properties.
     */
    public function create(
        ?string $referenceId,
        int $amount,
        // A) opsi saved card:
        ?string $paymentMethodId = null,
        // B) opsi token dari Xendit.js v3:
        ?string $oneTimeToken = null,   // one-time token (recommended untuk one-time)
        ?string $cardTokenId  = null,   // token_id (alternatif)
        // 3DS / return URLs:
        ?string $successReturnUrl = null,
        ?string $failureReturnUrl = null,
        ?string $cancelReturnUrl  = null,
        ?string $pendingReturnUrl = null,
        // Capture & initiator:
        ?string $captureMethod = 'AUTOMATIC',       // "AUTOMATIC"|"MANUAL"
        ?string $initiator     = null,              // "CUSTOMER"|"MERCHANT"
        // Channel properties khusus card:
        ?bool   $requireAuth         = null,        // OTP for BRI tokenized only; else ignored
        ?string $cardOnFileType      = null,        // "CUSTOMER_UNSCHEDULED"|"MERCHANT_UNSCHEDULED"|"RECURRING"
        ?string $merchantIdTag       = null,        // pilih MID tertentu
        ?string $cvv                 = null,        // bila issuer/acquirer mensyaratkan
        // Lainnya:
        ?array  $metadata = null,
        ?string $idempotencyKey = null,
        ?string $forUserId = null,
        ?string $withSplitRuleId = null
    ): array {
        $referenceId = $this->ensureReferenceId($referenceId, 'CARD');

        // 1) Top-level channel_properties (sesuai PaymentRequestParametersChannelProperties)
        $channelProps = array_filter([
            'success_return_url' => $successReturnUrl,
            'failure_return_url' => $failureReturnUrl,
            'cancel_return_url'  => $cancelReturnUrl,
            'pending_return_url' => $pendingReturnUrl,
            'require_auth'       => $requireAuth,
            'merchant_id_tag'    => $merchantIdTag,
            'cardonfile_type'    => $cardOnFileType,
            'cvv'                => $cvv,
        ], static fn($v) => !is_null($v));

        // 2) Bangun payload dasar
        $payload = [
            'reference_id'      => $referenceId,
            'amount'            => $amount,
            'currency'          => $this->defaultCurrency,
            'country'           => $this->defaultCountry,
            'capture_method'    => $captureMethod, // PaymentRequestCaptureMethod
            'initiator'         => $initiator,     // PaymentRequestInitiator (CUSTOMER|MERCHANT) - opsional
            'channel_properties' => $channelProps ?: null,
            'metadata'          => $metadata,
        ];

        // 3) Tentukan cara kirim method:
        if (!empty($paymentMethodId)) {
            // Saved payment method (recommended untuk subsequent)
            $payload['payment_method_id'] = $paymentMethodId;
        } else {
            // One-time / tokenized via Xendit.js
            $cardInfo = [];
            if (!empty($oneTimeToken)) {
                $cardInfo['one_time_token'] = $oneTimeToken;
            } elseif (!empty($cardTokenId)) {
                $cardInfo['token_id'] = $cardTokenId;
            } else {
                throw new \InvalidArgumentException('Provide either payment_method_id or one_time_token/token_id for CARD.');
            }

            $payload['payment_method'] = [
                'type'        => 'CARD',          // PaymentMethodType
                'reusability' => 'ONE_TIME_USE',  // untuk one-time token
                'card'        => [
                    // CardParameters – channel_properties (khusus channel card) *opsional* → kosongkan jika tidak perlu
                    'channel_properties' => (object)[], // atau hapus baris ini kalau tidak pakai
                    'card_information'   => $cardInfo,  // CardInformation
                ],
            ];
        }

        return $this->send($payload, $idempotencyKey, $forUserId, $withSplitRuleId);
    }
}
