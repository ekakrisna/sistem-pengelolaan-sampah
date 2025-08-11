<?php

namespace App\Services\Xendit\Methods;

class DirectDebitPaymentMethodService extends BaseXenditPaymentMethodService
{
    public function create(
        string $channelCode,                    // contoh PH: BPI|UBP
        string $reusability = 'MULTIPLE_USE',
        ?string $customerId = null,
        ?array  $customerObject = null,
        ?array  $channelProperties = null,      // tergantung bank
        ?array  $metadata = null,
        ?array  $billingInformation = null,
        ?string $forUserId = null
    ): array {
        $payload = [
            'type'         => 'DIRECT_DEBIT',
            'reusability'  => $reusability,
            'country'      => $this->defaultCountry,
            'direct_debit' => [
                'channel_code'       => strtoupper($channelCode),
                'channel_properties' => $channelProperties ?: (object)[],
            ],
            'metadata'            => $metadata,
            'billing_information' => $billingInformation,
        ];

        if ($customerId) $payload['customer_id'] = $customerId;
        elseif (!empty($customerObject)) $payload['customer'] = $customerObject;

        return $this->send($payload, $forUserId);
    }
}
