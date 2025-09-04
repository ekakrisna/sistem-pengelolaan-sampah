<?php

namespace App\Services\Xendits\PaymentRequest;

use App\Data\Xendit\PaymentRequest\PaymentsApiPayAndSaveData;
use App\Services\Xendits\Http\XenditService;

class PaymentPayAndSaveService extends XenditService
{
    public function create(PaymentsApiPayAndSaveData $data): array
    {
        return $this->post('/v3/payment_requests', $data->toPayload());
    }
}
