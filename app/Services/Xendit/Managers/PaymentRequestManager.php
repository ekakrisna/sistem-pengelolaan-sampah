<?php

namespace App\Services\Xendit\Managers;

use App\Data\CreatePaymentData;
use App\Enums\PaymentChannelCategory;
use App\Services\Xendit\Requests\CardsPaymentRequestService;
use App\Services\Xendit\Requests\DirectDebitPaymentRequestService;
use App\Services\Xendit\Requests\EwalletRedirectOneTimePaymentRequestService;
use App\Services\Xendit\Requests\EwalletTokenizedSubsequentPaymentRequestService;
use App\Services\Xendit\Requests\QrisDynamicFixedPaymentRequestService;
use App\Services\Xendit\Requests\RetailOutletOneTimePaymentRequestService;
use App\Services\Xendit\Requests\VirtualAccountFixedSingleUsePaymentRequestService;
use App\Services\Xendit\Queriers\PaymentRequestQueryService;
use RuntimeException;

class PaymentRequestManager
{
    public function __construct(
        protected EwalletRedirectOneTimePaymentRequestService $ewalletRedirect,
        protected EwalletTokenizedSubsequentPaymentRequestService $ewalletTokenized,
        protected QrisDynamicFixedPaymentRequestService $qrisDynamicFixed,
        protected VirtualAccountFixedSingleUsePaymentRequestService $vaSingleUseFixed,
        protected DirectDebitPaymentRequestService $directDebitPh,
        protected PaymentRequestQueryService $prQuery,
        protected RetailOutletOneTimePaymentRequestService $overTheCounterOneTime,
        protected CardsPaymentRequestService $cardsService
    ) {}

    public function create(CreatePaymentData $dto): array
    {
        // if (empty($dto->reference_id)) {
        //     $prefix = match ($dto->channel_category) {
        //         PaymentChannelCategory::EWALLET         => 'EWALLET',
        //         PaymentChannelCategory::QRIS            => 'QRIS',
        //         PaymentChannelCategory::VIRTUAL_ACCOUNT => 'VA',
        //         PaymentChannelCategory::DIRECT_DEBIT_PH => 'DDPH',
        //         PaymentChannelCategory::OVER_THE_COUNTER => 'OTC',
        //         PaymentChannelCategory::CARDS           => 'CARD',
        //         default => 'PAY',
        //     };
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
            PaymentChannelCategory::OVER_THE_COUNTER => $this->overTheCounterOneTime->create(
                referenceId: $dto->reference_id,
                amount: $dto->amount,
                channelCode: (string) $dto->channel_code,   // ALFAMART|INDOMARET
                payerName: (string) $dto->payer_name,
                expiresAt: $dto->expires_at?->utc(),
                paymentCode: $dto->payment_code,
                metadata: $dto->metadata,
                idempotencyKey: $dto->idempotency_key,
                forUserId: $dto->for_user_id,
                withSplitRuleId: $dto->with_split_rule_id,
            ),
            PaymentChannelCategory::CARDS => $this->cardsService->create(
                referenceId: $dto->reference_id,
                amount: $dto->amount,

                // pilih salah satu: saved card atau token
                paymentMethodId: $dto->payment_method_id,       // saved card
                oneTimeToken: $dto->card_one_time_token,     // Xendit.js one-time token
                cardTokenId: $dto->card_token_id,           // alternatif token_id

                // 3DS/return URLs (top-level channel_properties)
                successReturnUrl: $dto->success_return_url,
                failureReturnUrl: $dto->failure_return_url,
                cancelReturnUrl: $dto->cancel_return_url,
                pendingReturnUrl: $dto->pending_return_url,

                // capture & initiator
                captureMethod: $dto->capture_method ?? 'AUTOMATIC',
                initiator: $dto->initiator,               // CUSTOMER|MERCHANT

                // opsi channel_properties khusus kartu
                requireAuth: $dto->card_require_auth,
                cardOnFileType: $dto->card_on_file_type,
                merchantIdTag: $dto->card_merchant_id_tag,
                cvv: $dto->card_cvv,

                metadata: $dto->metadata,
                idempotencyKey: $dto->idempotency_key,
                forUserId: $dto->for_user_id,
                withSplitRuleId: $dto->with_split_rule_id
            ),
            default => throw new RuntimeException('Channel category not supported'),
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
