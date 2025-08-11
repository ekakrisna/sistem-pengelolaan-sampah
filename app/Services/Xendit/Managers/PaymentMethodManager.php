<?php

namespace App\Services\Xendit\Managers;

use RuntimeException;
use App\Data\PaymentMethod\CreatePaymentMethodData;
use App\Enums\PaymentMethodType;
use App\Services\Xendit\Queriers\PaymentMethodQueryService;
use App\Services\Xendit\Methods\EwalletPaymentMethodService;
use App\Services\Xendit\Methods\CardPaymentMethodService;
use App\Services\Xendit\Methods\DirectDebitPaymentMethodService;

class PaymentMethodManager
{
    public function __construct(
        protected EwalletPaymentMethodService     $wallet,
        protected CardPaymentMethodService        $card,
        protected DirectDebitPaymentMethodService $directDebit,
        protected PaymentMethodQueryService       $pmQuery
    ) {}

    /**
     * Router utama — gunakan DTO Spatie (CreatePaymentMethodData).
     */
    public function create(CreatePaymentMethodData $dto): array
    {
        // isi default ringan (currency/country/reusability) bila belum ada
        $dto = $dto->withDefaults(
            defaultCurrency: config('services.xendit.currency', 'IDR'),
            defaultCountry: config('services.xendit.country',  'ID'),
        );

        return match ($dto->type) {
            PaymentMethodType::EWALLET      => $this->createWallet($dto),
            PaymentMethodType::CARD         => $this->createCard($dto),
            PaymentMethodType::DIRECT_DEBIT => $this->createDirectDebit($dto),
            default => throw new RuntimeException('Payment method type not supported'),
        };
    }

    /** Ambil Payment Method by ID (helper query) */
    public function getById(string $paymentMethodId, ?string $forUserId = null): array
    {
        return $this->pmQuery->getById($paymentMethodId, $forUserId);
    }

    /** ===== EWALLET ===== */
    protected function createWallet(CreatePaymentMethodData $dto): array
    {
        return $this->wallet->create(
            channelCode: strtoupper((string) $dto->channel_code),
            reusability: $dto->reusability->value,
            customerId: $this->nn($dto->customer_id),
            customerObject: $dto->customer?->toArray(),                    // inline customer (opsional)
            successReturnUrl: $this->nn($dto->success_return_url),
            failureReturnUrl: $this->nn($dto->failure_return_url),
            cancelReturnUrl: $this->nn($dto->cancel_return_url),
            pendingReturnUrl: $this->nn($dto->pending_return_url),
            metadata: $dto->metadata ?: null,
            billingInformation: $dto->billing_information?->toArray(),
            forUserId: $this->nn($dto->for_user_id)
        );
    }

    /** ===== CARD (saved PM / linking) ===== */
    protected function createCard(CreatePaymentMethodData $dto): array
    {
        return $this->card->create(
            currency: $dto->currency ?? config('services.xendit.currency', 'IDR'),
            reusability: $dto->reusability->value,
            customerId: $this->nn($dto->customer_id),
            customerObject: $dto->customer?->toArray(),
            // channel properties:
            skipThreeDS: $dto->skip_three_d_secure,
            successReturnUrl: $this->nn($dto->success_return_url),
            failureReturnUrl: $this->nn($dto->failure_return_url),
            cardOnFileType: $dto->cardonfile_type?->value,
            expiresAt: $dto->expires_at?->toDateTimeImmutable(),
            installmentConfiguration: $dto->installment_configuration ?: null,
            merchantIdTag: $this->nn($dto->merchant_id_tag),
            // PCI path (jangan isi kalau tidak PCI DSS)
            cardInformation: $dto->card_information?->toArray(),
            metadata: $dto->metadata ?: null,
            billingInformation: $dto->billing_information?->toArray(),
            forUserId: $this->nn($dto->for_user_id)
        );
    }

    /** ===== DIRECT DEBIT ===== */
    protected function createDirectDebit(CreatePaymentMethodData $dto): array
    {
        return $this->directDebit->create(
            channelCode: strtoupper((string) $dto->channel_code),       // contoh PH: BPI|UBP
            reusability: $dto->reusability->value,
            customerId: $this->nn($dto->customer_id),
            customerObject: $dto->customer?->toArray(),
            channelProperties: $dto->channel_properties ?: null,              // properti spesifik bank
            metadata: $dto->metadata ?: null,
            billingInformation: $dto->billing_information?->toArray(),
            forUserId: $this->nn($dto->for_user_id)
        );
    }

    /**
     * Normalizer kecil: "" → null, trim jika string.
     */
    private function nn(mixed $v): mixed
    {
        if (is_string($v)) {
            $v = trim($v);
            return $v === '' ? null : $v;
        }
        return $v;
    }
}
