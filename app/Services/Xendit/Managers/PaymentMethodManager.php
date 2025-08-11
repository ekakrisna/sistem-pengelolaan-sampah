<?php

namespace App\Services\Xendit\Managers;

use App\Services\Xendit\Methods\CardPaymentMethodService;
use App\Services\Xendit\Methods\DirectDebitPaymentMethodService;
use App\Services\Xendit\Methods\EwalletPaymentMethodService;
use App\Services\Xendit\Queriers\PaymentMethodQueryService;
use RuntimeException;

class PaymentMethodManager
{
    public function __construct(
        protected EwalletPaymentMethodService     $wallet,
        protected CardPaymentMethodService        $card,
        protected DirectDebitPaymentMethodService $directDebit,
        protected PaymentMethodQueryService       $pmQuery

    ) {}

    /**
     * Router utama (mirip PaymentRequestManager::create).
     * $dto: object/array yang minimal punya key: type ('EWALLET'|'CARD'|'DIRECT_DEBIT').
     */
    public function create(object|array $dto): array
    {
        // akses gampang: pakai array_get style
        $get = fn(string $k, $d = null) => (is_array($dto) ? ($dto[$k] ?? $d) : ($dto->$k ?? $d));
        $type = strtoupper((string) $get('type'));

        return match ($type) {
            'EWALLET'      => $this->createWallet($dto),
            'CARD'         => $this->createCard($dto),
            'DIRECT_DEBIT' => $this->createDirectDebit($dto),
            default        => throw new RuntimeException('Payment method type not supported'),
        };
    }

    /** Ambil Payment Method by ID */
    public function getById(string $paymentMethodId, ?string $forUserId = null): array
    {
        return $this->pmQuery->getById($paymentMethodId, $forUserId);
    }

    /** ===== EWALLET ===== */
    protected function createWallet(object|array $dto): array
    {
        $get = fn(string $k, $d = null) => (is_array($dto) ? ($dto[$k] ?? $d) : ($dto->$k ?? $d));

        return $this->wallet->create(
            channelCode: (string) $get('channel_code'), // OVO|SHOPEEPAY|DANA|GOPAY|LINKAJA
            reusability: (string) ($get('reusability') ?? 'MULTIPLE_USE'),
            customerId: $get('customer_id'),
            customerObject: $get('customer'),
            successReturnUrl: $get('success_return_url'),
            failureReturnUrl: $get('failure_return_url'),
            cancelReturnUrl: $get('cancel_return_url'),
            pendingReturnUrl: $get('pending_return_url'),
            metadata: $get('metadata'),
            billingInformation: $get('billing_information'),
            forUserId: $get('for_user_id')
        );
    }

    /** ===== CARD ===== */
    protected function createCard(object|array $dto): array
    {
        $get = fn(string $k, $d = null) => (is_array($dto) ? ($dto[$k] ?? $d) : ($dto->$k ?? $d));

        return $this->card->create(
            currency: $get('currency'), // default ke config kalau null
            reusability: (string) ($get('reusability') ?? 'MULTIPLE_USE'),
            customerId: $get('customer_id'),
            customerObject: $get('customer'),
            skipThreeDS: $get('skip_three_d_secure'),
            successReturnUrl: $get('success_return_url'),
            failureReturnUrl: $get('failure_return_url'),
            cardOnFileType: $get('cardonfile_type'),
            expiresAt: $get('expires_at') instanceof \DateTimeInterface ? $get('expires_at') : null,
            installmentConfiguration: $get('installment_configuration'),
            merchantIdTag: $get('merchant_id_tag'),
            cardInformation: $get('card_information'), // ⚠️ hanya kalau PCI DSS
            metadata: $get('metadata'),
            billingInformation: $get('billing_information'),
            forUserId: $get('for_user_id')
        );
    }

    /** ===== DIRECT DEBIT ===== */
    protected function createDirectDebit(object|array $dto): array
    {
        $get = fn(string $k, $d = null) => (is_array($dto) ? ($dto[$k] ?? $d) : ($dto->$k ?? $d));

        return $this->directDebit->create(
            channelCode: (string) $get('channel_code'), // contoh PH: BPI|UBP
            reusability: (string) ($get('reusability') ?? 'MULTIPLE_USE'),
            customerId: $get('customer_id'),
            customerObject: $get('customer'),
            channelProperties: $get('channel_properties'),
            metadata: $get('metadata'),
            billingInformation: $get('billing_information'),
            forUserId: $get('for_user_id')
        );
    }
}
