<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Data\PickupFeeData;
use App\Http\Controllers\Controller;
use App\Http\Resources\PickupFee\PickupFeeCollection;
use App\Services\PickupFeeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PickupFeeController extends Controller
{
    use ApiResponse;

    /**
     * @var PickupFeeService
     */
    protected PickupFeeService $pickupFeeService;

    /**
     * DummyModel Constructor
     *
     * @param PickupFeeService $pickupFeeService
     *
     */
    public function __construct(PickupFeeService $pickupFeeService)
    {
        $this->pickupFeeService = $pickupFeeService;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'order_by',
            'village',
            'waste_type',
            'admin',
        ]);
        $pageSize = (int) $request->input('page_size', 10);
        $pickupFee = $this->pickupFeeService->paginate($filters, $pageSize);
        $data = new PickupFeeCollection(PickupFeeData::collect($pickupFee));
        return $this->successResponse($data, message: 'Pickup fees retrieved successfully.');
    }

    public function store(PickupFeeData $data): PickupFeeData|JsonResponse
    {
        try {
            $data = PickupFeeData::from($this->pickupFeeService->save($data->all()));
            return $this->successResponse($data, 'Pickup fee successfully created.');
        } catch (\Exception $exception) {
            report($exception);
            return $this->errorResponse(
                "Error::InternalServerError",
                $exception->getMessage(),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function show(int $id): PickupFeeData|JsonResponse
    {
        $user = PickupFeeData::from($this->pickupFeeService->getById($id));
        return $this->successResponse(
            data: $user,
            message: 'Pickup fee retrieved successfully.'
        );
    }

    public function update(PickupFeeData $data, int $id): PickupFeeData|JsonResponse
    {
        try {
            $data = PickupFeeData::from($this->pickupFeeService->update($data->all(), $id));
            return $this->successResponse($data, 'Pickup fee successfully updated.');
        } catch (\Exception $exception) {
            report($exception);
            return $this->errorResponse(
                "Error::InternalServerError",
                $exception->getMessage(),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->pickupFeeService->deleteById($id);
            return $this->successResponse(null, 'Pickup successfully deleted.');
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
