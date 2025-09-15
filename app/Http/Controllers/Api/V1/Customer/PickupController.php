<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Data\PickupData;
use App\Data\UserData;
use App\Enums\PickupEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Pickup\PickupCollection;
use App\Services\PickupService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PickupController extends Controller
{
    use ApiResponse;

    /**
     * @var PickupService
     */
    protected PickupService $pickupService;
    protected $user;

    /**
     * DummyModel Constructor
     *
     * @param PickupService $pickupService
     *
     */
    public function __construct(PickupService $pickupService, Request $request)
    {
        $this->pickupService = $pickupService;
        $this->user = UserData::from($request->user());
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'order_by',
            'village',
            'waste_type',
            'status',
            'customer',
            'petugas'
        ]);
        $pageSize = (int) $request->input('page_size', 10);
        $pickups = $this->pickupService->paginate($filters, $pageSize, $this->user);
        $data = new PickupCollection(PickupData::collect($pickups));
        return $this->successResponse($data, message: 'Pickups retrieved successfully.');
    }

    public function show(int $id): PickupData|JsonResponse
    {
        $data = PickupData::from($this->pickupService->getById($id, $this->user));
        return $this->successResponse(
            data: $data,
            message: 'Pickup retrieved successfully.'
        );
    }

    public function store(PickupData $data): PickupData|JsonResponse
    {
        try {
            $data = PickupData::from($this->pickupService->save($data, $this->user));
            return $this->successResponse($data, 'Pickup successfully created.');
        } catch (\Exception $exception) {
            report($exception);
            [$name, $message, $code, $errors] = $this->normalizeException($exception);
            return $this->errorResponse($name, $message, statusCode: $code, errors: $errors);
        }
    }

    public function cancel(int $id): PickupData|JsonResponse
    {
        try {
            $payload = PickupData::from(...[$this->pickupService->getById($id, $this->user), 'status' => PickupEnum::canceled->value]);
            $data = PickupData::from($this->pickupService->update($payload, $id, $this->user));
            return $this->successResponse($data, 'Pickup schedule successfully updated.');
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse(
                "Error::NotFound",
                $e->getMessage(),
                statusCode: Response::HTTP_NOT_FOUND
            );
        } catch (\Exception $exception) {
            report($exception);
            [$name, $message, $code, $errors] = $this->normalizeException($exception);
            return $this->errorResponse($name, $message, statusCode: $code, errors: $errors);
        }
    }
}
