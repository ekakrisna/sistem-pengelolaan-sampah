<?php

namespace App\Http\Controllers\Api\V1\WasteType;

use App\Data\WasteTypeData;
use App\Http\Controllers\Controller;
use App\Http\Resources\WasteType\WasteTypeCollection;
use Illuminate\Http\JsonResponse;
use App\Services\WasteTypeService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WasteTypeController extends Controller
{

    use ApiResponse;

    /**
     * @var WasteTypeService
     */
    protected WasteTypeService $wasteTypeService;

    /**
     * DummyModel Constructor
     *
     * @param WasteTypeService $wasteTypeService
     *
     */
    public function __construct(WasteTypeService $wasteTypeService)
    {
        $this->wasteTypeService = $wasteTypeService;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'order_by']);
        $pageSize = (int) $request->input('page_size', 10);
        $users = $this->wasteTypeService->paginate($filters, $pageSize);
        $data = new WasteTypeCollection(WasteTypeData::collect($users));

        return $this->successResponse($data, message: 'Users retrieved successfully.');
    }

    public function store(WasteTypeData $data): WasteTypeData|\Illuminate\Http\JsonResponse
    {
        try {
            return WasteTypeData::from($this->wasteTypeService->save($data->all()));
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id): WasteTypeData
    {
        return WasteTypeData::from($this->wasteTypeService->getById($id));
    }

    public function update(WasteTypeData $data, int $id): WasteTypeData|\Illuminate\Http\JsonResponse
    {
        try {
            return WasteTypeData::from($this->wasteTypeService->update($data->all(), $id));
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $this->wasteTypeService->deleteById($id);
            return response()->json(['message' => 'Deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
