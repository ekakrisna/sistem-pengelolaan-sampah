<?php

namespace App\Services\Xendit\Methods;

class CardPaymentMethodService extends BaseXenditPaymentMethodService
{
    public function create(
        ?string $currency = null,                // required by SDK; default to config
        string $reusability = 'MULTIPLE_USE',
        ?string $customerId = null,
        ?array  $customerObject = null,
        // channel properties:
        ?bool   $skipThreeDS = null,
        ?string $successReturnUrl = null,
        ?string $failureReturnUrl = null,
        ?string $cardOnFileType = null,         // CUSTOMER_UNSCHEDULED|MERCHANT_UNSCHEDULED|RECURRING
        ?\DateTimeInterface $expiresAt = null,
        ?array  $installmentConfiguration = null,
        ?string $merchantIdTag = null,
        // PCI path (optional; only if PCI DSS)
        ?array  $cardInformation = null,        // ['card_number','expiry_month','expiry_year','cardholder_name','cvv']
        ?array  $metadata = null,
        ?array  $billingInformation = null,
        ?string $forUserId = null
    ): array {
        $currency ??= $this->defaultCurrency;

        $channelProps = array_filter([
            'skip_three_d_secure'     => $skipThreeDS,
            'success_return_url'      => $successReturnUrl,
            'failure_return_url'      => $failureReturnUrl,
            'cardonfile_type'         => $cardOnFileType,
            'expires_at'              => $expiresAt,
            'installment_configuration' => $installmentConfiguration,
            'merchant_id_tag'         => $merchantIdTag,
        ], fn($v) => !is_null($v));

        $card = [
            'currency'           => $currency,
            'channel_properties' => $channelProps ?: (object)[],
        ];

        $allowPci = (bool) config('services.xendit.cards_allow_pci', false);
        if (!empty($cardInformation) && !$allowPci) {
            $card['card_information'] = $cardInformation; // ⚠️ only if PCI
        }

        $payload = [
            'type'               => 'CARD',
            'reusability'        => $reusability,
            'country'            => $this->defaultCountry,
            'card'               => $card,
            'metadata'           => $metadata,
            'billing_information' => $billingInformation,
        ];

        if ($customerId) $payload['customer_id'] = $customerId;
        elseif (!empty($customerObject)) $payload['customer'] = $customerObject;

        return $this->send($payload, $forUserId);
    }
}
