<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Data\CreatePaymentData;
use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Services\Xendit\Manager\XenditPaymentManager;
use App\Services\Xendit\Queriers\PaymentRequestQueryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    use ApiResponse;

    protected $user;

    public function __construct(
        protected XenditPaymentManager $xenditPaymentManager,
        protected PaymentRequestQueryService $paymentRequestQueryService,
        protected Request $request
    ) {
        $this->user = UserData::from($request->user());
    }

    /**
     * Create a new payment request (E-Wallet, QRIS, VA, Tokenized)
     */
    public function create(CreatePaymentData $data): JsonResponse
    {
        try {
            // $validated = $request->validate(CreatePaymentData::rules());
            $data = CreatePaymentData::from($data)
                ->withDefaults(
                    defaultCurrency: config('service.xendit.currency', 'IDR'),
                    defaultCountry: config('service.xendit.country', 'ID'),
                );

            $result = $this->xenditPaymentManager->create($data);

            return $this->successResponse(
                data: $result,
                message: 'Payment request created successfully.'
            );
        } catch (\Throwable $e) {
            report($e);
            return $this->errorResponse(
                name: 'Error::InternalServerError',
                message: $e->getMessage(),
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function show(string $id, Request $request)
    {
        try {
            $forUserId = $request->query('for_user_id');

            $result = $this->xenditPaymentManager->getPaymentRequestById(
                $id,
                $forUserId
            );
            if (empty($result)) {
                return $this->errorResponse(
                    name: 'Error::NotFound',
                    message: 'Payment request not found.',
                    statusCode: Response::HTTP_NOT_FOUND
                );
            }

            return $this->successResponse(
                data: $result,
                message: 'Payment request found.'
            );
        } catch (\Throwable $e) {
            // Kalau RuntimeException kembalikan kode dari exception code bila tersedia
            $code = (int) $e->getCode();
            $httpCode = ($code >= 400 && $code < 600) ? $code : Response::HTTP_UNPROCESSABLE_ENTITY;

            return $this->errorResponse(
                name: 'Error::InternalServerError',
                message: $e->getMessage(),
                statusCode: $httpCode
            );
        }
    }

    // /**
    //  * Get captures for a Payment Request
    //  */
    // public function captures(string $prId, Request $request): JsonResponse
    // {
    //     $forUser = $request->query('for_user_id');
    //     $limit   = (int) $request->query('limit', 50);

    //     $result = $this->paymentRequestService->getCaptures($prId, $forUser, $limit);

    //     return $this->successResponse(
    //         data: $result,
    //         message: 'Captures found.'
    //     );
    // }
}
