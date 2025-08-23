<?php

namespace App\Http\Controllers\Api\V1\Xendit;

use App\Http\Controllers\Controller;
use App\Services\Xendits\Payment\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{

    use ApiResponse;

    public function __construct(
        protected PaymentService $payment
    ) {}

    public function showByPaymentId(Request $request, string $id): JsonResponse
    {
        try {
            $forUserId    = $request->query('for_user_id') ?? $request->header('for-user-id');
            $splitRuleId  = $request->query('split_rule_id') ?? $request->header('with-split-rule');

            $this->payment->setContext($forUserId, $splitRuleId);

            $data = $this->payment->getByPaymentId($id);

            return $this->successResponse($data, 'Payment request (by payment id) retrieved successfully.');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeXenditException($th);
            return $this->errorResponse(name: $name, message: $message, statusCode: $code);
        }
    }
}
