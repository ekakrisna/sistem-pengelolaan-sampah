<?php

namespace App\Http\Controllers\Api\V1\Customer\Xendit;

use App\Data\Xendit\Platform\CreateAccount\CreateAccountRequestData;
use App\Data\Xendit\Platform\ListAccounts\ListAccountsQueryData;
use App\Http\Controllers\Controller;
use App\Services\Xendits\Customer\XenPlatformService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected XenPlatformService $service
    ) {}

    public function getAccounts(ListAccountsQueryData $request): JsonResponse
    {
        try {
            $data = $this->service->getAccounts($request);
            return $this->successResponse($data);
        } catch (\Throwable $th) {
            [$name, $message, $code, $errors] = $this->normalizeXenditException($th);
            return $this->errorResponse(
                name: $name,
                message: $message,
                statusCode: $code,
                errors: $errors,
            );
        }
    }

    public function createAccount(CreateAccountRequestData $dto): JsonResponse
    {
        try {
            $data = $this->service->createAccount($dto);
            return $this->successResponse($data, 'Account created successfully.');
        } catch (\Throwable $th) {
            [$name, $message, $code, $errors] = $this->normalizeXenditException($th);
            return $this->errorResponse(
                name: $name,
                message: $message,
                statusCode: $code,
                errors: $errors,
            );
        }
    }
}
