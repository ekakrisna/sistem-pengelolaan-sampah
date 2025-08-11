<?php

namespace App\Services\Xendit\Requests;

use App\Services\Xendit\Requests\BaseXenditPaymentRequestService;

class EwalletRedirectOneTimePaymentRequestService extends BaseXenditPaymentRequestService
{
    /**
     * @param string|null $referenceId
     * @param int         $amount                IDR dalam integer
     * @param string      $channelCode           SHOPEEPAY|OVO|DANA|LINKAJA|GOPAY
     * @param string|null $successReturnUrl      dipakai utk channel redirect (bukan OVO)
     * @param string|null $mobileNumber          dipakai utk OVO (format +62...)
     */
    public function create(
        ?string $referenceId,
        int $amount,
        string $channelCode,
        ?string $successReturnUrl = null,
        ?string $mobileNumber = null,
        ?array $metadata = null,
        ?string $idempotencyKey = null,
        ?string $forUserId = null,
        ?string $withSplitRuleId = null
    ): array {
        $referenceId = $this->ensureReferenceId($referenceId, 'EWALLET');
        $channelCode = strtoupper($channelCode);

        // susun channel_properties sesuai channel
        $channelProps = [];
        if ($channelCode === 'OVO') {
            // normalize nomor agar ke format +62
            $channelProps['mobile_number'] = $this->normalizeMsisdn($mobileNumber);
        } else {
            if (!empty($successReturnUrl)) {
                $channelProps['success_return_url'] = $successReturnUrl;
            }
        }

        $payload = [
            'reference_id' => $referenceId,
            'amount'       => $amount,
            'currency'     => $this->defaultCurrency,
            'country'      => $this->defaultCountry,
            'payment_method' => [
                'type'        => 'EWALLET',
                'reusability' => 'ONE_TIME_USE',
                'ewallet'     => [
                    'channel_code'       => $channelCode,
                    'channel_properties' => $channelProps,
                ],
            ],
            'metadata' => $metadata,
        ];

        return $this->send($payload, $idempotencyKey, $forUserId, $withSplitRuleId);
    }

    /** Ubah "0812..." -> "+62812..." kalau user kirim nomor lokal */
    private function normalizeMsisdn(?string $msisdn): ?string
    {
        if (!$msisdn) return null;
        $msisdn = trim($msisdn);
        if (str_starts_with($msisdn, '+')) return $msisdn;
        if (str_starts_with($msisdn, '0')) return '+62' . substr($msisdn, 1);
        // fallback: kalau sudah "62..." tanpa plus
        if (preg_match('/^62\d+$/', $msisdn)) return '+' . $msisdn;
        return $msisdn;
    }
}
