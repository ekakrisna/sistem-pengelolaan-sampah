<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Data\PickupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\PickupRequest;
use App\Services\PickupService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PickupController extends Controller
{
    use ApiResponse;

    /**
     * @var PickupService
     */
    protected PickupService $pickupService;

    /**
     * DummyModel Constructor
     *
     * @param PickupService $pickupService
     *
     */
    public function __construct(PickupService $pickupService)
    {
        $this->pickupService = $pickupService;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'order_by',
            'village',
            'waste_type',
            'admin',
            'status',
            'customer',
            'petugas'
        ]);
        $pageSize = (int) $request->input('page_size', 10);
        $pickups = $this->pickupService->paginate($filters, $pageSize);
        $data = PickupData::paginatedResponse($pickups);
        return $this->successResponse($data, message: 'Pickups retrieved successfully.');
    }

    public function store(PickupRequest $request): PickupData|JsonResponse
    {
        try {
            $payload = PickupData::from($request->validated());
            $data = PickupData::from($this->pickupService->save($payload));
            return $this->successResponse($data, 'Pickup successfully created.');
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

    public function show(int $id): PickupData|JsonResponse
    {
        $data = PickupData::from($this->pickupService->getById($id));
        return $this->successResponse(
            data: $data,
            message: 'Pickup retrieved successfully.'
        );
    }

    public function update(PickupRequest $request, int $id): PickupData|JsonResponse
    {
        try {
            $payload = PickupData::from($request->validated());
            $data = PickupData::from($this->pickupService->update($payload, $id));
            return $this->successResponse($data, 'Pickup schedule successfully updated.');
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

    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->pickupService->deleteById($id);
            return $this->successResponse(
                ['deleted' => (bool) $deleted],
                'Pickup successfully deleted.'
            );
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
