<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Data\PaymentData;
use App\Data\TransactionData;
use App\Data\UserData;
use App\Data\Xendit\PaymentRequest\PaymentsApiPayData;
use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Services\TransactionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    protected $user;

    public function __construct(
        protected Request $request,
        protected PaymentService $paymentService
    ) {
        $this->user = $request->user() ? UserData::from($request->user()) : null;
        $this->paymentService = $paymentService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'customer',
                'status',
                'payment_method',
                'start_date',
                'end_date',
                'order_by',
            ]);
            $pageSize = (int) $request->input('page_size', 10);
            $payments = $this->paymentService->paginate(
                $filters,
                $pageSize,
                $this->user
            );
            $data = PaymentData::paginatedResponse($payments);

            return $this->successResponse(
                data: $data,
                message: "Payments retrieved successfully."
            );
        } catch (\Throwable $th) {
            [$name, $message, $code, $errors] = $this->normalizeException($th);
            return $this->errorResponse(
                name: $name,
                message: $message,
                statusCode: $code,
                errors: $errors,
            );
        }
    }

    public function show(int $id): PaymentData|JsonResponse
    {
        try {
            $payment = $this->paymentService->getById($id, $this->user);
            $data = PaymentData::from($payment);
            return $this->successResponse(
                data: $data,
                message: "Payment retrieved successfully."
            );
        } catch (\Throwable $th) {
            [$name, $message, $code, $errors] = $this->normalizeException($th);
            return $this->errorResponse(
                name: $name,
                message: $message,
                statusCode: $code,
                errors: $errors,
            );
        }
    }

    public function pay(Request $request, int $transactionId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'channel_code'        => 'required|string|max:50',
                'channel_properties'  => 'nullable|array',
                'metadata'            => 'nullable|array',
            ]);

            $result = $this->paymentService->createPaymentRequest(
                $transactionId,
                $this->user,
                $validated
            );

            return $this->successResponse(
                data: [
                    'payment'     => PaymentData::from($result['payment']),
                    'transaction' => TransactionData::from($result['transaction']),
                    'xendit'      => $result['xendit'],
                ],
                message: 'Payment request created successfully.'
            );
        } catch (\Throwable $th) {
            [$name, $message, $code, $errors] = $this->normalizeException($th);
            return $this->errorResponse($name, $message, statusCode: $code, errors: $errors);
        }
    }
}
