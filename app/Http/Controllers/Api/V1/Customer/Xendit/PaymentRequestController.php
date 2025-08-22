<?php

namespace App\Http\Controllers\Api\V1\Customer\Xendit;

use App\Data\Xendit\PaymentRequest\PaymentRequestListQueryData;
use App\Http\Controllers\Controller;
use App\Services\Xendits\PaymentRequest\PaymentRequestService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentRequestController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PaymentRequestService $paymentRequests
    ) {}

    public function getPaymentRequests(PaymentRequestListQueryData $query): JsonResponse
    {
        try {
            $data = $this->paymentRequests->getPaymentRequests($query);
            return $this->successResponse($data, 'Payment requests retrieved successfully.');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeXenditException($th);
            return $this->errorResponse(name: $name, message: $message, statusCode: $code);
        }
    }

    public function showById(Request $request, string $id): JsonResponse
    {
        try {
            // Ambil context dari query atau header client
            $forUserId    = $request->query('for_user_id') ?? $request->header('for-user-id');
            $splitRuleId  = $request->query('split_rule_id') ?? $request->header('with-split-rule');

            // Set default context untuk request ini
            $this->paymentRequests->setContext($forUserId, $splitRuleId);

            $data = $this->paymentRequests->getById($id);

            return $this->successResponse($data, 'Payment request retrieved successfully.');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeXenditException($th);
            return $this->errorResponse(name: $name, message: $message, statusCode: $code);
        }
    }

    public function cancelPaymentRequest(Request $request, string $id): JsonResponse
    {
        try {
            $forUserId    = $request->query('for_user_id') ?? $request->header('for-user-id');
            $splitRuleId  = $request->query('split_rule_id') ?? $request->header('with-split-rule');

            $this->paymentRequests->setContext($forUserId, $splitRuleId);

            $data = $this->paymentRequests->cancel($id);

            return $this->successResponse($data, 'Payment request canceled successfully.');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeXenditException($th);
            return $this->errorResponse(name: $name, message: $message, statusCode: $code);
        }
    }

    public function simulatePaymentRequest(Request $request, string $id): JsonResponse
    {
        try {
            $forUserId    = $request->query('for_user_id') ?? $request->header('for-user-id');
            $splitRuleId  = $request->query('split_rule_id') ?? $request->header('with-split-rule');

            $this->paymentRequests->setContext($forUserId, $splitRuleId);

            $data = $this->paymentRequests->simulate($id, $request->amount);

            return $this->successResponse($data, 'Payment request simulated successfully.');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeXenditException($th);
            return $this->errorResponse(name: $name, message: $message, statusCode: $code);
        }
    }
}
