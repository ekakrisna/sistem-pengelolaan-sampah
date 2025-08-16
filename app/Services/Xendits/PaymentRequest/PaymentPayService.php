<?php

namespace App\Services\Xendits\PaymentRequest;

use App\Data\Xendit\Pay\Card\Response\PaymentRequestCardPayResponseData;
use App\Data\Xendit\Pay\PaymentsApiPayData;
use App\Data\Xendit\Pay\Present\Response\PaymentRequestPresentToCustomerResponseData;
use App\Data\Xendit\Pay\Redirect\Response\PaymentRequestRedirectPayResponseData;
use App\Services\Xendits\Http\XenditService;

class PaymentPayService extends XenditService
{
    public function payWithCard(PaymentsApiPayData $data): PaymentRequestCardPayResponseData
    {
        $res = $this->post('/v3/payment_requests', $data->toPayload());
        return PaymentRequestCardPayResponseData::fromXendit($res);
    }

    public function payWithQris(PaymentsApiPayData $data): PaymentRequestRedirectPayResponseData
    {
        $res = $this->post('/v3/payment_requests', $data->toPayload());
        return PaymentRequestRedirectPayResponseData::from($res);
    }

    public function payWithVirtualAccount(PaymentsApiPayData $data): PaymentRequestPresentToCustomerResponseData
    {
        $res = $this->post('/v3/payment_requests', $data->toPayload());
        return PaymentRequestPresentToCustomerResponseData::fromXendit($res);
    }

    public function payWithEwallet(PaymentsApiPayData $data): PaymentRequestRedirectPayResponseData
    {
        $res = $this->post('/v3/payment_requests', $data->toPayload());
        return PaymentRequestRedirectPayResponseData::fromXendit($res);
    }
}
