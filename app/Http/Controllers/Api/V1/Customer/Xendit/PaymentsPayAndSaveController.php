<?php

namespace App\Http\Controllers\Api\V1\Customer\Xendit;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use App\Data\Xendit\PaymentRequest\PaymentsApiPayAndSaveData;
use App\Services\Xendits\PaymentRequest\PaymentPayAndSaveService;

class PaymentsPayAndSaveController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PaymentPayAndSaveService $service
    ) {}

    public function create(PaymentsApiPayAndSaveData $dto, Request $request): JsonResponse
    {
        try {
            $forUserId       = $request->header('for-user-id')     ?? $request->query('for_user_id');
            $splitRuleId     = $request->header('with-split-rule') ?? $request->query('split_rule_id');

            $this->service->setContext($forUserId, $splitRuleId);

            $data = $this->service->create($dto);

            return $this->successResponse($data, 'PAY_AND_SAVE created.');
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
