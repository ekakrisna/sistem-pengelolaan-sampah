<?php

namespace App\Services\Xendit\Methods;

class EwalletPaymentMethodService extends BaseXenditPaymentMethodService
{
    public function create(
        string $channelCode,                    // OVO|SHOPEEPAY|DANA|GOPAY|LINKAJA
        string $reusability = 'MULTIPLE_USE',
        ?string $customerId = null,
        ?array $customerObject = null,          // ['reference_id'=>..,'type'=>'INDIVIDUAL',...]
        ?string $successReturnUrl = null,
        ?string $failureReturnUrl = null,
        ?string $cancelReturnUrl  = null,
        ?string $pendingReturnUrl = null,
        ?array  $metadata = null,
        ?array  $billingInformation = null,
        ?string $forUserId = null
    ): array {
        $payload = [
            'type'        => 'EWALLET',
            'reusability' => $reusability,
            'country'     => $this->defaultCountry,
            'ewallet'     => [
                'channel_code'       => strtoupper($channelCode),
                'channel_properties' => array_filter([
                    'success_return_url' => $successReturnUrl,
                    'failure_return_url' => $failureReturnUrl,
                    'cancel_return_url'  => $cancelReturnUrl,
                    'pending_return_url' => $pendingReturnUrl,
                ], fn($v) => !is_null($v)),
            ],
            'metadata'            => $metadata,
            'billing_information' => $billingInformation,
        ];

        if ($customerId) $payload['customer_id'] = $customerId;
        elseif (!empty($customerObject)) $payload['customer'] = $customerObject;

        return $this->send($payload, $forUserId);
    }
}
