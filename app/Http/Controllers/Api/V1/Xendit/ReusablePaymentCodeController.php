<?php

namespace App\Http\Controllers\Api\V1\Xendit;

use App\Data\Xendit\PaymentRequest\PaymentsApiReusablePaymentCodeData;
use App\Http\Controllers\Controller;
use App\Services\Xendits\PaymentRequest\ReusablePaymentCodeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReusablePaymentCodeController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReusablePaymentCodeService $service

    ) {}

    public function createNoAmount(PaymentsApiReusablePaymentCodeData $dto, Request $request): JsonResponse
    {
        try {
            $forUserId   = $request->header('for-user-id')     ?? $request->query('for_user_id');
            $splitRuleId = $request->header('with-split-rule') ?? $request->query('split_rule_id');

            $this->service->setContext($forUserId, $splitRuleId);

            $res = $this->service->createNoAmount($dto);

            return $this->successResponse($res, 'Reusable payment code created.');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeException($th);
            return $this->errorResponse(
                name: $name,
                message: $message,
                statusCode: $code
            );
        }
    }

    public function createWithAmount(PaymentsApiReusablePaymentCodeData $dto, Request $request): JsonResponse
    {
        try {
            $forUserId   = $request->header('for-user-id')     ?? $request->query('for_user_id');
            $splitRuleId = $request->header('with-split-rule') ?? $request->query('split_rule_id');

            $this->service->setContext($forUserId, $splitRuleId);

            $data = $this->service->createWithAmount($dto);

            return $this->successResponse($data, 'Reusable payment code created (with amount).');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeException($th);
            return $this->errorResponse(
                name: $name,
                message: $message,
                statusCode: $code
            );
        }
    }
}
