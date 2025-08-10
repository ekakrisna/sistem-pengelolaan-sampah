<?php

namespace App\Services\Xendit\Manager;

use App\Data\CreatePaymentData;
use App\Enums\PaymentChannelCategory;
use App\Services\Xendit\Methods\DirectDebitPHSubsequentService;
use App\Services\Xendit\Methods\EwalletRedirectOneTimeService;
use App\Services\Xendit\Methods\EwalletTokenizedSubsequentService;
use App\Services\Xendit\Methods\QrisDynamicFixedService;
use App\Services\Xendit\Methods\RetailOutletOneTimeService;
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
        protected RetailOutletOneTimeService $retailOutletOneTime,
    ) {}

    public function create(CreatePaymentData $dto): array
    {
        // if (empty($dto->reference_id)) {
        //     $prefix = match ($dto->channel_category) {
        //         PaymentChannelCategory::EWALLET => 'EWALLET',
        //         PaymentChannelCategory::QRIS => 'QRIS',
        //         PaymentChannelCategory::VIRTUAL_ACCOUNT => 'VA',
        //         PaymentChannelCategory::DIRECT_DEBIT_PH => 'DDPH',
        //         PaymentChannelCategory::RETAIL_OUTLET => 'RO',
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
                expiresAt: $dto->expires_at?->utc(),
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
            PaymentChannelCategory::RETAIL_OUTLET => $this->retailOutletOneTime->create(
                referenceId: $dto->reference_id,
                amount: $dto->amount,
                channelCode: (string) $dto->channel_code,   // 'ALFAMART'|'INDOMARET'
                payerName: (string) $dto->payer_name,
                expiresAt: $dto->expires_at?->utc(),
                paymentCode: $dto->payment_code,
                metadata: $dto->metadata,
                idempotencyKey: $dto->idempotency_key,
                forUserId: $dto->for_user_id,
                withSplitRuleId: $dto->with_split_rule_id,
            ),
        };
    }

    public function getPaymentRequestById(string $id, ?string $forUserId = null): array
    {
        return $this->prQuery->getById($id, $forUserId);
    }

    private function createEwallet(CreatePaymentData $dto): array
    {
        if (!empty($dto->payment_method_id)) {
            // tokenized subsequent
            return $this->ewalletTokenized->create(
                referenceId: $dto->reference_id ?? null,
                amount: $dto->amount,
                paymentMethodId: $dto->payment_method_id,
                metadata: $dto->metadata,
                idempotencyKey: $dto->idempotency_key,
                forUserId: $dto->for_user_id,
                withSplitRuleId: $dto->with_split_rule_id,
            );
        }

        // redirect / OVO push
        return $this->ewalletRedirect->create(
            referenceId: $dto->reference_id ?? null,
            amount: $dto->amount,
            channelCode: (string) $dto->ewallet_channel_code,
            successReturnUrl: $dto->success_return_url,
            mobileNumber: $dto->ewallet_mobile_number ?? null,
            metadata: $dto->metadata,
            idempotencyKey: $dto->idempotency_key,
            forUserId: $dto->for_user_id,
            withSplitRuleId: $dto->with_split_rule_id,
        );
    }
}
