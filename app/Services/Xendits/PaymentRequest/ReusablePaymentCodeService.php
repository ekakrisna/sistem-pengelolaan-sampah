<?php

namespace App\Services\Xendits\PaymentRequest;

use App\Data\Xendit\PaymentRequest\PaymentsApiReusablePaymentCodeData;
use App\Services\Xendits\Http\XenditService;

class ReusablePaymentCodeService extends XenditService
{
    public function createNoAmount(PaymentsApiReusablePaymentCodeData $data): array
    {
        return $this->post('/v3/payment_requests', $data->toPayload());
    }

    public function createWithAmount(PaymentsApiReusablePaymentCodeData $data): array
    {
        return $this->post('/v3/payment_requests', $data->toPayload());
    }
}
