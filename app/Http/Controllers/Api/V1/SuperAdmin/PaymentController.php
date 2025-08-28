<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Data\PaymentData;
use App\Http\Controllers\Controller;
use App\Http\Resources\Payment\PaymentCollection;
use App\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    use ApiResponse;

    /**
     * @var PaymentService
     */
    protected PaymentService $paymentService;

    /**
     * DummyModel Constructor
     *
     * @param PaymentService $paymentService
     *
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'order_by',
            'start_date',
            'end_date',
            'waste_type',
            'payment_method',
            'status',
            'customer',
            'search'
        ]);
        $pageSize = (int) $request->input('page_size', 10);
        $pickups = $this->paymentService->paginate($filters, $pageSize);
        $data = new PaymentCollection(PaymentData::collect($pickups));
        return $this->successResponse($data, message: 'Payments retrieved successfully.');
    }

    public function store(PaymentData $data): PaymentData|JsonResponse
    {
        try {
            $data = PaymentData::from($this->paymentService->save($data->all()));
            return $this->successResponse($data, 'Payment successfully created.');
        } catch (\Exception $exception) {
            report($exception);
            return $this->errorResponse(
                "Error::InternalServerError",
                $exception->getMessage(),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function show(int $id): PaymentData|JsonResponse
    {
        $data = PaymentData::from($this->paymentService->getById($id));
        return $this->successResponse(
            data: $data,
            message: 'Pickup retrieved successfully.'
        );
    }

    public function update(PaymentData $data, int $id): PaymentData|JsonResponse
    {
        try {
            $data = PaymentData::from($this->paymentService->update($data->all(), $id));
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
            $deleted = $this->paymentService->deleteById($id);
            return $this->successResponse(
                ['deleted' => (bool) $deleted],
                'Payment successfully deleted.'
            );
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
