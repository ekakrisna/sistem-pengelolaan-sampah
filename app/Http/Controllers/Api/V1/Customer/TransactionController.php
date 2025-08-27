<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Data\TransactionData;
use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Http\Resources\Transaction\TransactionCollection;
use App\Services\TransactionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponse;

    /**
     * @var TransactionService
     */
    protected TransactionService $transactionService;
    protected $user;

    /**
     * DummyModel Constructor
     *
     * @param TransactionService $transactionService
     *
     */
    public function __construct(
        TransactionService $transactionService,
        Request $request
    ) {
        $this->transactionService = $transactionService;
        $this->user = UserData::from($request->user());
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

        return $this->successResponse(
            $data,
            message: 'Transactions retrieved successfully.'
        );
    }

    public function show(int $id): TransactionData|JsonResponse
    {
        $data = TransactionData::from($this->transactionService->getById($id, $this->user));
        return $this->successResponse(
            data: $data,
            message: 'Transaction retrieved successfully.'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = TransactionData::from($request)->toArray();
        $transaction = $this->transactionService->save($data, $this->user);
        return $this->successResponse(
            data: TransactionData::from($transaction),
            message: 'Transaction created successfully.'
        );
    }
}
