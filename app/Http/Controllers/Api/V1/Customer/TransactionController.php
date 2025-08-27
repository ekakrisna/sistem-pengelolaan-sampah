<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Data\TransactionData;
use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\CartRequest;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\Transaction\TransactionCollection;
use App\Models\Transaction;
use App\Models\UserAddress;
use App\Services\TransactionService;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    use ApiResponse, AuthorizesRequests;

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

    /** ---------------- Cart Endpoints ---------------- */

    public function cart(): JsonResponse
    {
        $cart = $this->transactionService->getOrCreateDraftCart($this->user->id);

        // authorize view milik sendiri
        $this->authorize('view', $cart);

        $data = TransactionData::from($cart);
        return $this->successResponse(
            data: $data,
            message: 'Draft cart retrieved successfully.'
        );
    }

    public function addItem(CartRequest $request): JsonResponse
    {
        try {
            $uid = $this->user->id ?? null;
            $trxId = $request->integer('transaction_id');

            $request->validated();
            $payload = $request->only([
                'item_type',
                'user_address_id',
                'pickup_schedule_id',
                'pickup_fee_id',
                'unit_amount',
                'qty',
                'description',
                'meta'
            ]);
            $trx = $this->transactionService->getById($trxId, $this->user);

            $this->authorize('addItem', $trx);

            $item = $this->transactionService->addItemToCart(
                $trxId,
                $payload,
                $uid
            );

            return $this->successResponse($item, 'Item added to cart successfully.');
        } catch (\Throwable $th) {
            [$name, $message, $code, $errors] = $this->normalizeException($th);
            return $this->errorResponse(
                $name,
                $message,
                statusCode: $code,
                errors: $errors
            );
        }
    }

    public function removeItem(Request $request, int $itemId): JsonResponse
    {
        try {
            $uid = $this->user->id;
            $trxId = $request->integer('transaction_id');

            $request->validate([
                'transaction_id' => [
                    'required',
                    'integer',
                    Rule::exists('transactions', 'id')
                        ->where(fn($q) => $q->where('customer_id', $uid)
                            ->where('status', 'draft')),
                ],
            ]);

            $trx = $this->transactionService->getById($trxId, $this->user);
            $this->authorize('removeItem', $trx);

            $deleted = $this->transactionService->removeItemFromCart(
                $trxId,
                $itemId,
                $uid
            );

            return $this->successResponse(
                ['deleted' => (bool) $deleted],
                $deleted ? 'Item removed successfully.' : 'Item not found.'
            );
        } catch (\Throwable $th) {
            [$name, $message, $code, $errors] = $this->normalizeException($th);
            return $this->errorResponse(
                $name,
                $message,
                statusCode: $code,
                errors: $errors
            );
        }
    }
}
