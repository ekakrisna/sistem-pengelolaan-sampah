<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Data\WasteTypeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\WasteTypeRequest;
use App\Http\Resources\WasteType\WasteTypeCollection;
use App\Services\WasteTypeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
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
        $filters = $request->only(['search', 'order_by', 'admin']);
        $pageSize = (int) $request->input('page_size', 10);
        $users = $this->wasteTypeService->paginate($filters, $pageSize);
        $data = WasteTypeData::paginatedResponse($users);

        return $this->successResponse($data, message: 'Waste types retrieved successfully.');
    }

    public function store(WasteTypeRequest $request): WasteTypeData|JsonResponse
    {
        try {
            $payload = WasteTypeData::from($request->validated());
            $wasteType = WasteTypeData::from($this->wasteTypeService->save($payload));

            return $this->successResponse($wasteType, 'Waste type successfully created.');
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

    public function show(int $id): WasteTypeData|JsonResponse
    {
        $wasteType = WasteTypeData::from($this->wasteTypeService->getById($id));
        return $this->successResponse(
            data: $wasteType,
            message: 'Waste type retrieved successfully.'
        );
    }

    public function update(WasteTypeData $data, int $id): WasteTypeData|JsonResponse
    {
        try {
            $payload = WasteTypeData::from($data->all());
            $data = WasteTypeData::from($this->wasteTypeService->update($payload, $id));
            return $this->successResponse($data, 'Waste type successfully updated.');
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
            $deleted = $this->wasteTypeService->deleteById($id);
            return $this->successResponse(
                ['deleted' => (bool) $deleted],
                'Waste type successfully deleted.'
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
