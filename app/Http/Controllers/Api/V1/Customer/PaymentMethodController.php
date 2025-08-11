<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Data\PaymentMethod\CreatePaymentMethodData;
use App\Enums\PaymentMethodReusability;
use App\Http\Controllers\Controller;
use App\Services\Xendit\Managers\PaymentMethodManager;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentMethodController extends Controller
{
    use ApiResponse;
    public function __construct(
        private PaymentMethodManager $pmManager
    ) {}

    public function store(Request $request): JsonResponse
    {
        try {
            // Validasi pakai rules dari DTO
            $validated = $request->validate(CreatePaymentMethodData::rules());

            // Bangun DTO + defaults (currency/country/reusability)
            $dto = CreatePaymentMethodData::from($validated)->withDefaults(
                defaultCurrency: config('services.xendit.currency', 'IDR'),
                defaultCountry: config('services.xendit.country', 'ID'),
                defaultReusability: PaymentMethodReusability::MULTIPLE_USE
            );

            // Delegasi ke manager
            $result = $this->pmManager->create($dto);

            return $this->successResponse($result, 'Payment method created.');
        } catch (\Throwable $e) {
            report($e);
            return $this->errorResponse(
                name: 'Error::InternalServerError',
                message: $e->getMessage(),
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function show(string $id, Request $request): JsonResponse
    {
        try {
            $forUserId = $request->query('for_user_id');

            $result = $this->pmManager->getById($id, $forUserId);

            return $this->successResponse($result, 'Payment method found.');
        } catch (\Throwable $e) {
            report($e);
            return $this->errorResponse(
                name: 'Error::InternalServerError',
                message: $e->getMessage(),
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
