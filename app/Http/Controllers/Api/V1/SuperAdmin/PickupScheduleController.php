<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Data\PickupScheduleData;
use App\Http\Controllers\Controller;
use App\Http\Requests\PickupScheduleRequest;
use App\Http\Resources\PickupSchedule\PickupScheduleCollection;
use App\Models\Pickup;
use App\Services\PickupScheduleService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PickupScheduleController extends Controller
{
    use ApiResponse;

    /**
     * @var PickupScheduleService
     */
    protected PickupScheduleService $pickupScheduleService;

    /**
     * DummyModel Constructor
     *
     * @param PickupScheduleService $pickupScheduleService
     *
     */
    public function __construct(PickupScheduleService $pickupScheduleService)
    {
        $this->pickupScheduleService = $pickupScheduleService;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'order_by',
            'village',
            'waste_type',
            'start_pickup_time',
            'end_pickup_time',
            'day_of_week',
            'admin'
        ]);
        $pageSize = (int) $request->input('page_size', 10);
        $pickupScheduleService = $this->pickupScheduleService->paginate($filters, $pageSize);
        $data = PickupScheduleData::paginatedResponse($pickupScheduleService);
        return $this->successResponse($data, message: 'Pickup schedule retrieved successfully.');
    }

    public function store(PickupScheduleRequest $request): PickupScheduleData|JsonResponse
    {
        try {
            $payload = PickupScheduleData::from($request->validated());
            $data = PickupScheduleData::from($this->pickupScheduleService->save($payload));
            return $this->successResponse($data, 'Pickup schedule successfully created.');
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

    public function show(int $id): PickupScheduleData|JsonResponse
    {
        $user = PickupScheduleData::from($this->pickupScheduleService->getById($id));
        return $this->successResponse(
            data: $user,
            message: 'Pickup schedule retrieved successfully.'
        );
    }

    public function update(PickupScheduleRequest $request, int $id): PickupScheduleData|JsonResponse
    {
        try {
            $payload = PickupScheduleData::from($request->validated());
            $data = PickupScheduleData::from($this->pickupScheduleService
                ->update($payload, $id));
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
            $this->pickupScheduleService->deleteById($id);
            return $this->successResponse(null, 'Pickup schedule successfully deleted.');
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
