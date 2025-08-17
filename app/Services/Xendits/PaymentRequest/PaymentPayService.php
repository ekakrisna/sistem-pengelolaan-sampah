<?php

namespace App\Services\Xendits\PaymentRequest;

use App\Data\Xendit\Pay\PaymentsApiPayData;
use App\Services\Xendits\Http\XenditService;

class PaymentPayService extends XenditService
{
    public function payWithPresentToCustomer(PaymentsApiPayData $data): array
    {
        $res = $this->post('/v3/payment_requests', $data->toPayload());
        return $res;
    }

    public function payWithRedirect(PaymentsApiPayData $data): array
    {
        $res = $this->post('/v3/payment_requests', $data->toPayload());
        return $res;
    }

    public function payWithCard(PaymentsApiPayData $data): array
    {
        $res = $this->post('/v3/payment_requests', $data->toPayload());
        return $res;
    }
}
