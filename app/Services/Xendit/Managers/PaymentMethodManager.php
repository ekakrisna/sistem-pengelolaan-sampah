<?php

namespace App\Services\Xendit\Managers;

use App\Services\Xendit\Methods\CardPaymentMethodService;
use App\Services\Xendit\Methods\DirectDebitPaymentMethodService;
use App\Services\Xendit\Methods\EwalletPaymentMethodService;

class PaymentMethodManager
{
    public function __construct(
        private EwalletPaymentMethodService     $wallet,
        private CardPaymentMethodService        $card,
        private DirectDebitPaymentMethodService $dd
    ) {}

    /** EWALLET */
    public function createWallet(array $args): array
    {
        return $this->wallet->create(
            channelCode: (string) $args['channel_code'],
            reusability: $args['reusability']     ?? 'MULTIPLE_USE',
            customerId: $args['customer_id']      ?? null,
            customerObject: $args['customer']         ?? null,
            successReturnUrl: $args['success_return_url'] ?? null,
            failureReturnUrl: $args['failure_return_url'] ?? null,
            cancelReturnUrl: $args['cancel_return_url']  ?? null,
            pendingReturnUrl: $args['pending_return_url'] ?? null,
            metadata: $args['metadata']         ?? null,
            billingInformation: $args['billing_information'] ?? null,
            forUserId: $args['for_user_id']      ?? null,
        );
    }

    /** CARDS */
    public function createCard(array $args): array
    {
        return $this->card->create(
            currency: $args['currency']         ?? null, // default ke config
            reusability: $args['reusability']       ?? 'MULTIPLE_USE',
            customerId: $args['customer_id']       ?? null,
            customerObject: $args['customer']          ?? null,
            skipThreeDS: $args['skip_three_d_secure'] ?? null,
            successReturnUrl: $args['success_return_url'] ?? null,
            failureReturnUrl: $args['failure_return_url'] ?? null,
            cardOnFileType: $args['cardonfile_type']     ?? null,
            expiresAt: $args['expires_at'] instanceof \DateTimeInterface ? $args['expires_at'] : null,
            installmentConfiguration: $args['installment_configuration'] ?? null,
            merchantIdTag: $args['merchant_id_tag']    ?? null,
            cardInformation: $args['card_information']   ?? null, // ⚠️ PCI only
            metadata: $args['metadata']           ?? null,
            billingInformation: $args['billing_information'] ?? null,
            forUserId: $args['for_user_id']        ?? null,
        );
    }

    /** DIRECT DEBIT */
    public function createDirectDebit(array $args): array
    {
        return $this->dd->create(
            channelCode: (string) $args['channel_code'],
            reusability: $args['reusability']       ?? 'MULTIPLE_USE',
            customerId: $args['customer_id']        ?? null,
            customerObject: $args['customer']           ?? null,
            channelProperties: $args['channel_properties'] ?? null,
            metadata: $args['metadata']           ?? null,
            billingInformation: $args['billing_information'] ?? null,
            forUserId: $args['for_user_id']        ?? null,
        );
    }
}
