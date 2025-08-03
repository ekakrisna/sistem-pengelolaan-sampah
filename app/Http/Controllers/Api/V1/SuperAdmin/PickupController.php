<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Data\PickupData;
use App\Http\Controllers\Controller;
use App\Http\Resources\Pickup\PickupCollection;
use App\Services\PickupService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $data = new PickupCollection(PickupData::collect($pickups));
        return $this->successResponse($data, message: 'Pickups retrieved successfully.');
    }

    public function store(PickupData $data): PickupData|JsonResponse
    {
        try {
            $data = PickupData::from($this->pickupService->save($data->all()));
            return $this->successResponse($data, 'Pickup successfully created.');
        } catch (\Exception $exception) {
            report($exception);
            return $this->errorResponse(
                "Error::InternalServerError",
                $exception->getMessage(),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function show(int $id): PickupData|JsonResponse
    {
        $user = PickupData::from($this->pickupService->getById($id));
        return $this->successResponse(
            data: $user,
            message: 'Pickup retrieved successfully.'
        );
    }

    public function update(PickupData $data, int $id): PickupData|JsonResponse
    {
        try {
            $data = PickupData::from($this->pickupService->update($data->all(), $id));
            return $this->successResponse($data, 'Pickup schedule successfully updated.');
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
            $this->pickupService->deleteById($id);
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
