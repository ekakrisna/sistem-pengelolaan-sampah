<?php

namespace App\Services\Xendit\Manager;

use App\Data\CreatePaymentData;
use App\Enums\PaymentChannelCategory;
use App\Services\Xendit\Methods\DirectDebitPHSubsequentService;
use App\Services\Xendit\Methods\EwalletRedirectOneTimeService;
use App\Services\Xendit\Methods\EwalletTokenizedSubsequentService;
use App\Services\Xendit\Methods\QrisDynamicFixedService;
use App\Services\Xendit\Methods\VirtualAccountFixedSingleUseService;
use App\Services\Xendit\Queriers\PaymentRequestQueryService;
use RuntimeException;

class XenditPaymentManager
{
    public function __construct(
        protected EwalletRedirectOneTimeService $ewalletRedirect,
        protected EwalletTokenizedSubsequentService $ewalletTokenized,
        protected QrisDynamicFixedService $qrisDynamicFixed,
        protected VirtualAccountFixedSingleUseService $vaSingleUseFixed,
        protected DirectDebitPHSubsequentService $directDebitPh,
        protected PaymentRequestQueryService $prQuery,

    ) {}

    public function create(CreatePaymentData $dto): array
    {
        // if (empty($dto->reference_id)) {
        //     $prefix = match ($dto->channel_category) {
        //         PaymentChannelCategory::EWALLET => 'EWALLET',
        //         PaymentChannelCategory::QRIS => 'QRIS',
        //         PaymentChannelCategory::VIRTUAL_ACCOUNT => 'VA',
        //         PaymentChannelCategory::DIRECT_DEBIT_PH => 'DDPH',
        //         default => 'PAY',
        //     };
        //     // pakai helper yang sama (boleh di-duplikasi kecil di manager, atau diekspor ke trait)
        //     $dto->reference_id = $prefix . '-' . now()->format('YmdHis') . '-' . uniqid();
        // }

        return match ($dto->channel_category) {
            PaymentChannelCategory::EWALLET => $this->createEwallet($dto),
            PaymentChannelCategory::QRIS => $this->qrisDynamicFixed->create(
                referenceId: $dto->reference_id,
                amount: $dto->amount,
                metadata: $dto->metadata,
                idempotencyKey: $dto->idempotency_key,
                forUserId: $dto->for_user_id,
                withSplitRuleId: $dto->with_split_rule_id
            ),
            PaymentChannelCategory::VIRTUAL_ACCOUNT => $this->vaSingleUseFixed->create(
                referenceId: $dto->reference_id,
                amount: $dto->amount,
                bankCode: (string) $dto->bank_code,
                customerName: (string) $dto->customer_name,
                expiresAtUtc: $dto->expires_at?->utc() ?? now('UTC')->addDay(),
                vaReferenceId: $dto->va_reference_id,
                metadata: $dto->metadata,
                idempotencyKey: $dto->idempotency_key,
                forUserId: $dto->for_user_id,
                withSplitRuleId: $dto->with_split_rule_id
            ),
            PaymentChannelCategory::DIRECT_DEBIT_PH => $this->directDebitPh->create(
                referenceId: $dto->reference_id,
                amount: $dto->amount,
                paymentMethodId: (string) $dto->payment_method_id,
                metadata: $dto->metadata,
                idempotencyKey: $dto->idempotency_key,
                forUserId: $dto->for_user_id,
                withSplitRuleId: $dto->with_split_rule_id
            ),
            PaymentChannelCategory::RETAIL_OUTLET => throw new RuntimeException('RETAIL_OUTLET is not implemented yet.'),
        };
    }

    public function getPaymentRequestById(string $id, ?string $forUserId = null): array
    {
        return $this->prQuery->getById($id, $forUserId);
    }

    private function createEwallet(CreatePaymentData $dto): array
    {
        // Tokenized subsequent — jika paymentMethodId ada
        if (!empty($dto->payment_method_id)) {
            return $this->ewalletTokenized->create(
                referenceId: $dto->reference_id,
                amount: $dto->amount,
                paymentMethodId: $dto->payment_method_id,
                metadata: $dto->metadata,
                idempotencyKey: $dto->idempotency_key,
                forUserId: $dto->for_user_id,
                withSplitRuleId: $dto->with_split_rule_id,
            );
        }

        // Redirect one-time — gunakan channel_code + success_return_url
        return $this->ewalletRedirect->create(
            referenceId: $dto->reference_id,
            amount: $dto->amount,
            channelCode: (string) $dto->ewallet_channel_code,
            successReturnUrl: (string) $dto->success_return_url,
            metadata: $dto->metadata,
            idempotencyKey: $dto->idempotency_key,
            forUserId: $dto->for_user_id,
            withSplitRuleId: $dto->with_split_rule_id
        );
    }
}
