<?php

namespace App\Services\Xendits\PaymentRequest;

use App\Data\Xendit\PaymentRequest\PaymentsApiPayWithTokenData;
use App\Services\Xendits\Http\XenditService;

class PaymentWithTokenService extends XenditService
{
    public function payWithPaymentToken(PaymentsApiPayWithTokenData $data): array
    {
        return $this->post('/v3/payment_requests', $data->toPayload());
    }
}
