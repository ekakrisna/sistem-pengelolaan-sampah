<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Data\PickupScheduleData;
use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Http\Resources\PickupSchedule\PickupScheduleCollection;
use App\Services\PickupScheduleService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
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
    public function __construct(
        PickupScheduleService $pickupScheduleService,
    ) {
        $this->pickupScheduleService = $pickupScheduleService;
    }

    public function schedules(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'order_by',
            'village',
            'waste_type',
            'day_of_week',
        ]);
        $pageSize = (int) $request->input('page_size', 10);
        $pickupScheduleService = $this->pickupScheduleService->paginate($filters, $pageSize);
        $data = new PickupScheduleCollection(PickupScheduleData::collect($pickupScheduleService));
        return $this->successResponse($data, message: 'Pickup schedule retrieved successfully.');
    }

    public function fees(Request $request): JsonResponse
    {
        $filters = $request->only([
            'order_by',
            'village',
            'waste_type',
        ]);
        $pageSize = (int) $request->input('page_size', 10);
        $pickupFee = $this->pickupScheduleService->paginate($filters, $pageSize);
        $data = new PickupScheduleCollection(PickupScheduleData::collect($pickupFee));
        return $this->successResponse($data, message: 'Pickup fees retrieved successfully.');
    }
}
