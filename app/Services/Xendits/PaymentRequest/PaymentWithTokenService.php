<?php

namespace App\Services\Xendits\PaymentRequest;

use App\Data\Xendit\Common\PaymentRequestResponseData;
use App\Data\Xendit\Pay\Token\PayWithPaymentTokenData;
use App\Services\Xendits\Http\XenditService;

class PaymentWithTokenService extends XenditService
{
    // ... metode lain (card/qris/va/ewallet)

    /** PAY with Payment Token */
    public function payWithPaymentToken(PayWithPaymentTokenData $data): PaymentRequestResponseData
    {
        $res = $this->post('/v3/payment_requests', $data->toPayload());
        return PaymentRequestResponseData::fromXendit($res);
    }
}
