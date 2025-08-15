<?php

namespace App\Services\Xendit;

use App\Data\Xendit\Pay\Card\CardPayData;
use App\Data\Xendit\Pay\Qris\QrisPayData;
use App\Data\Xendit\Pay\Va\VaPayData;
use App\Data\Xendit\Pay\Ewallet\EwalletPayData;
use App\Data\Xendit\Common\PaymentRequestResponseData;
use App\Services\Xendits\Http\XenditService;

class PaymentPayService extends XenditService
{
    public function payWithCard(CardPayData $data): PaymentRequestResponseData
    {
        $res = $this->post('/v3/payment_requests', $data->toPayload());
        return PaymentRequestResponseData::fromXendit($res);
    }

    public function payWithQris(QrisPayData $data): PaymentRequestResponseData
    {
        $res = $this->post('/v3/payment_requests', $data->toPayload());
        return PaymentRequestResponseData::fromXendit($res);
    }

    public function payWithVirtualAccount(VaPayData $data): PaymentRequestResponseData
    {
        $res = $this->post('/v3/payment_requests', $data->toPayload());
        return PaymentRequestResponseData::fromXendit($res);
    }

    public function payWithEwallet(EwalletPayData $data): PaymentRequestResponseData
    {
        $res = $this->post('/v3/payment_requests', $data->toPayload());
        return PaymentRequestResponseData::fromXendit($res);
    }
}
