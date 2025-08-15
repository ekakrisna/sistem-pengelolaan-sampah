<?php

namespace App\Services\Xendits\PaymentRequest;

use App\Data\Xendit\Common\PaymentRequestResponseData;
use App\Data\Xendit\PayAndSave\PayAndSaveData;
use App\Services\Xendits\Http\XenditService;

class PaymentPayAndSaveService extends XenditService
{
    public function create(PayAndSaveData $data): PaymentRequestResponseData
    {
        $res = $this->post('/v3/payment_requests', $data->toPayload());
        return PaymentRequestResponseData::fromXendit($res);
    }
}
