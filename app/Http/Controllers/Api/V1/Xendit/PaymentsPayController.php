<?php

namespace App\Http\Controllers\Api\V1\Xendit;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Data\Xendit\PaymentRequest\PaymentsApiPayData;
use App\Services\Xendits\PaymentRequest\PaymentPayService;

class PaymentsPayController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PaymentPayService $service
    ) {}

    /**
     * 1.1 Present to Customer — Create one-off payment
     * Contoh: VA/QR yang langsung menampilkan kode ke customer (PRESENT_TO_CUSTOMER)
     */
    public function presentOneOff(PaymentsApiPayData $dto, Request $request): JsonResponse
    {
        try {
            $forUserId   = $request->header('for-user-id')     ?? $request->query('for_user_id');
            $splitRuleId = $request->header('with-split-rule') ?? $request->query('split_rule_id');

            $this->service->setContext($forUserId, $splitRuleId);

            $data = $this->service->payWithPresentToCustomer($dto);

            return $this->successResponse($data, 'PAY (present, one-off) created.');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeException($th);
            return $this->errorResponse(
                name: $name,
                message: $message,
                statusCode: $code
            );
        }
    }

    /**
     * 1.2 Present to Customer — Create one-off payment with specific payment code
     * Contoh: VA dengan "virtual_account_number" spesifik
     */
    public function presentOneOffWithSpecificCode(PaymentsApiPayData $dto, Request $request): JsonResponse
    {
        try {
            // context headers (opsional)
            $forUserId   = $request->header('for-user-id')     ?? $request->query('for_user_id');
            $splitRuleId = $request->header('with-split-rule') ?? $request->query('split_rule_id');

            $this->service->setContext($forUserId, $splitRuleId);

            $data = $this->service->payWithPresentToCustomer($dto);

            return $this->successResponse($data, 'PAY (present, specific code) created.');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeException($th);
            return $this->errorResponse(
                name: $name,
                message: $message,
                statusCode: $code
            );
        }
    }

    /**
     * 2.1 Redirect Customer — Create one-off payment with customer object
     * Contoh: e-wallet (OVO/DANA/SHOPEEPAY/LINKAJA) + "customer"
     */
    public function redirectWithCustomer(PaymentsApiPayData $dto, Request $request): JsonResponse
    {
        try {
            // context headers (opsional)
            $forUserId   = $request->header('for-user-id')     ?? $request->query('for_user_id');
            $splitRuleId = $request->header('with-split-rule') ?? $request->query('split_rule_id');

            // kamu bisa setContext(...) sekali untuk instance,
            // atau langsung kirim via argumen createRaw di bawah
            $this->service->setContext($forUserId, $splitRuleId);

            $data = $this->service->payWithRedirect($dto);

            return $this->successResponse($data, 'PAY (redirect, with customer) created.');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeException($th);
            return $this->errorResponse(
                name: $name,
                message: $message,
                statusCode: $code
            );
        }
    }

    /**
     * 2.2 Redirect Customer — Create one-off payment with no customer object
     */
    public function redirectNoCustomer(PaymentsApiPayData $dto, Request $request): JsonResponse
    {
        try {
            // context headers (opsional)
            $forUserId   = $request->header('for-user-id')     ?? $request->query('for_user_id');
            $splitRuleId = $request->header('with-split-rule') ?? $request->query('split_rule_id');

            // kamu bisa setContext(...) sekali untuk instance,
            // atau langsung kirim via argumen createRaw di bawah
            $this->service->setContext($forUserId, $splitRuleId);

            $data = $this->service->payWithRedirect($dto);
            return $this->successResponse($data, 'PAY (redirect, no customer) created.');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeException($th);
            return $this->errorResponse(
                name: $name,
                message: $message,
                statusCode: $code
            );
        }
    }

    // /**
    //  * 3. Create with Card full PAN — CARDS + card_details
    //  */
    // public function cardsFullPan(PaymentsApiPayWithCardFullPanData $dto, Request $request): JsonResponse
    // {
    //     try {
    //         $this->applyContext($request);

    //         $payload = $dto->toPayload();
    //         $raw = $this->service->createRaw($payload);

    //         $resp = PaymentRequestCardPayResponseData::from($raw);
    //         return $this->successResponse($resp, 'PAY (cards full PAN) created.');
    //     } catch (\Throwable $th) {
    //         return $this->xenditError($th);
    //     }
    // }
}
