<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Services\PaymentRequestService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    use ApiResponse;

    protected $user;

    public function __construct(
        protected PaymentRequestService $service,
        protected Request $request
    ) {
        $this->user = UserData::from($request->user());
    }

    /**
     * Create a new payment request (E-Wallet, QRIS, VA, Tokenized)
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id'        => ['nullable', 'integer', 'exists:users,id'], // bila customer login, akan di-override
            'pickup_id'          => ['required', 'integer', 'exists:pickups,id'],
            'method'             => ['required', Rule::in(['EWALLET', 'QR_CODE', 'VIRTUAL_ACCOUNT', 'TOKENIZED'])],

            // items is required as array of objects
            'items'              => ['required', 'array', 'min:1'],
            'items.*.pickup_fee_id' => ['required', 'integer', 'exists:pickup_fees,id'],
            'items.*.qty'            => ['required', 'integer', 'min:1'],

            // optional PR fields
            'channel_code'       => ['nullable', 'string'],
            'ewallet_phone'      => ['nullable', 'string'],
            'success_return_url' => ['nullable', 'url'],
            'va_customer_name'   => ['nullable', 'string'],
            'va_expires_at'      => ['nullable', 'date'],
            'payment_method_id'  => ['nullable', 'string'],
            'metadata'           => ['nullable', 'array'],
            'for_user_id'        => ['nullable', 'string'],
            'idempotency_key'    => ['nullable', 'string'],
            'with_split_rule'    => ['nullable', 'string'],
            'reference_id'       => ['nullable', 'string'],
        ]);

        $payment = $this->service->create($validated, $this->user);

        return $this->successResponse(
            data: $payment,
            message: 'Payment request created.'
        );
    }

    /**
     * Get payment request by Xendit Payment Request ID
     */
    public function show(string $prId, Request $request): JsonResponse
    {
        $forUser = $request->query('for_user_id');
        $result  = $this->service->getByPaymentRequestId($prId, $forUser);

        return $this->successResponse(
            data: $result,
            message: 'Payment request found.'
        );
    }

    /**
     * Get captures for a Payment Request
     */
    public function captures(string $prId, Request $request): JsonResponse
    {
        $forUser = $request->query('for_user_id');
        $limit   = (int) $request->query('limit', 50);

        $result = $this->service->getCaptures($prId, $forUser, $limit);

        return $this->successResponse(
            data: $result,
            message: 'Captures found.'
        );
    }
}
