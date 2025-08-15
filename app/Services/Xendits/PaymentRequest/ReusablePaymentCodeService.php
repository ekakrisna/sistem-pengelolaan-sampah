<?php

namespace App\Services\Xendits\PaymentRequest;

use App\Services\Xendits\Http\XenditService;

class ReusablePaymentCodeService extends XenditService
{
    /**
     * Create Reusable Payment Code (no amount)
     *
     * @param string $referenceId
     * @param string $channelCode
     * @param string $country default ID
     * @param string $currency default IDR
     * @return array
     */
    public function createNoAmount(
        string $referenceId,
        string $channelCode,
        string $country = 'ID',
        string $currency = 'IDR'
    ): array {
        $payload = [
            'reference_id' => $referenceId,
            'type'         => 'REUSABLE_PAYMENT_CODE',
            'country'      => $country,
            'currency'     => $currency,
            'channel_code' => $channelCode,
        ];

        return $this->post('/v3/payment_requests', $payload);
    }

    /**
     * Create Reusable Payment Code with amount
     *
     * @param string $referenceId
     * @param string $channelCode
     * @param int|float $amount
     * @param array $channelProperties
     * @param string $country
     * @param string $currency
     * @return array
     */
    public function createWithAmount(
        string $referenceId,
        string $channelCode,
        int|float $amount,
        array $channelProperties,
        string $country = 'ID',
        string $currency = 'IDR'
    ): array {
        $payload = [
            'reference_id'       => $referenceId,
            'type'               => 'REUSABLE_PAYMENT_CODE',
            'country'            => $country,
            'currency'           => $currency,
            'channel_code'       => $channelCode,
            'request_amount'     => $amount,
            'channel_properties' => $channelProperties,
        ];

        return $this->post('/v3/payment_requests', $payload);
    }
}
