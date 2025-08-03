<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Data\TransactionData;
use App\Http\Controllers\Controller;
use App\Http\Resources\Transaction\TransactionCollection;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{
    use ApiResponse;

    /**
     * @var TransactionService
     */
    protected TransactionService $transactionService;

    /**
     * DummyModel Constructor
     *
     * @param TransactionService $transactionService
     *
     */
    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'order_by',
            'village',
            'waste_type',
            'admin',
            'status',
            'start_date',
            'end_date',
            'payment_method'
        ]);
        $pageSize = (int) $request->input('page_size', 10);
        $pickups = $this->transactionService->paginate($filters, $pageSize);
        $data = new TransactionCollection(TransactionData::collect($pickups));

        return $this->successResponse($data, message: 'Transactions retrieved successfully.');
    }

    public function store(TransactionData $data): TransactionData|JsonResponse
    {
        try {
            $data = TransactionData::from($this->transactionService->save($data->all()));
            return $this->successResponse($data, 'Transaction successfully created.');
        } catch (\Exception $exception) {
            report($exception);
            return $this->errorResponse(
                "Error::InternalServerError",
                $exception->getMessage(),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function show(int $id): TransactionData|JsonResponse
    {
        $data = TransactionData::from($this->transactionService->getById($id));
        return $this->successResponse(
            data: $data,
            message: 'Transaction retrieved successfully.'
        );
    }

    public function update(TransactionData $data, int $id): TransactionData|JsonResponse
    {
        try {
            $data = TransactionData::from($this->transactionService->update($data->all(), $id));
            return $this->successResponse($data, 'Transaction successfully updated.');
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
            $this->transactionService->deleteById($id);
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
