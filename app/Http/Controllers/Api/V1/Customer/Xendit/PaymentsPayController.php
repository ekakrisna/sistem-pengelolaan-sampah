<?php

namespace App\Http\Controllers\Api\V1\Customer\Xendit;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Data\Xendit\Pay\PaymentsApiPayData;
use App\Services\Xendits\PaymentRequest\PaymentPayService;

class PaymentsPayController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PaymentPayService $service
    ) {}


    public function create_qris(PaymentsApiPayData $dto, Request $request): JsonResponse
    {
        try {
            // (Jika pipes Spatie Data belum aktif, bisa manual:)
            // $dto::validate($request->all());

            // context headers (opsional)
            $forUserId   = $request->header('for-user-id')     ?? $request->query('for_user_id');
            $splitRuleId = $request->header('with-split-rule') ?? $request->query('split_rule_id');

            // kamu bisa setContext(...) sekali untuk instance,
            // atau langsung kirim via argumen createRaw di bawah
            $this->service->setContext($forUserId, $splitRuleId);

            // kirim payload ke Xendit
            // $payload = $dto->toPayload();
            $res = $this->service->payWithQris($dto);

            return $this->successResponse($res, 'Payment request created.');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeXenditException($th);
            return $this->errorResponse(
                name: $name,
                message: $message,
                statusCode: $code
            );
        }
    }
}
