<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\User\UserCollection;
use App\Models\User;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * @var UserService
     */
    protected UserService $userService;

    /**
     * DummyModel Constructor
     *
     * @param UserService $userService
     *
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'role', 'status', 'order_by']);
        $pageSize = (int) $request->input('page_size', 10);
        $users = $this->userService->paginate($filters, $pageSize);
        $data = UserData::paginatedResponse($users);
        return $this->successResponse($data, message: 'Users retrieved successfully.');
    }

    public function store(UserRequest $request): UserData|JsonResponse
    {
        try {
            $payload = UserData::from($request->validated());
            $user = UserData::from($this->userService->save($payload));
            return $this->successResponse($user, 'User successfully created.');
        } catch (\Exception $exception) {
            report($exception);
            [$name, $message, $code, $errors] = $this->normalizeException($exception);
            return $this->errorResponse(
                $name,
                $message,
                statusCode: $code,
                errors: $errors
            );
        }
    }

    public function show(int $user): UserData|JsonResponse
    {
        $user = UserData::from($this->userService->getById($user));
        return $this->successResponse(
            data: $user,
            message: 'User retrieved successfully.'
        );
    }

    public function update(UserRequest $request, int $id): UserData|JsonResponse
    {
        try {
            $payload = UserData::from($request->validated());
            $data = UserData::from($this->userService->update($payload, $id));
            return $this->successResponse($data, 'User successfully updated.');
        } catch (\Exception $exception) {
            report($exception);
            [$name, $message, $code, $errors] = $this->normalizeException($exception);
            return $this->errorResponse(
                $name,
                $message,
                statusCode: $code,
                errors: $errors
            );
        }
    }

    public function destroy(int $user): JsonResponse
    {
        try {
            $this->userService->deleteById($user);
            return $this->successResponse(null, 'User successfully deleted.');
        } catch (\Exception $exception) {
            report($exception);
            [$name, $message, $code, $errors] = $this->normalizeException($exception);
            return $this->errorResponse(
                $name,
                $message,
                statusCode: $code,
                errors: $errors
            );
        }
    }
}
