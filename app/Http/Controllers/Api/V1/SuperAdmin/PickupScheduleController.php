<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Data\PickupScheduleData;
use App\Http\Controllers\Controller;
use App\Http\Resources\PickupSchedule\PickupScheduleCollection;
use App\Models\PickupSchedule;
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
        $data = new PickupScheduleCollection(PickupScheduleData::collect($pickupScheduleService));
        return $this->successResponse($data, message: 'Pickup Schedule retrieved successfully.');
    }

    public function store(PickupScheduleData $data): PickupScheduleData|JsonResponse
    {
        try {
            $data = PickupScheduleData::from($this->pickupScheduleService->save($data->all()));
            return $this->successResponse($data, 'Pickup schedule successfully created.');
        } catch (\Exception $exception) {
            report($exception);
            return $this->errorResponse(
                "Error::InternalServerError",
                $exception->getMessage(),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
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

    public function update(PickupScheduleData $data, int $id): PickupScheduleData|\Illuminate\Http\JsonResponse
    {
        try {
            return PickupScheduleData::from($this->pickupScheduleService->update($data->all(), $id));
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $this->pickupScheduleService->deleteById($id);
            return $this->successResponse(null, 'Pickup schedule successfully deleted.');
        } catch (\Exception $exception) {
            report($exception);
            return $this->errorResponse(
                "Error::InternalServerError",
                $exception->getMessage(),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
