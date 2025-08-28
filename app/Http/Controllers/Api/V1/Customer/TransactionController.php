<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Data\TransactionData;
use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\CartRequest;
use App\Http\Resources\Transaction\TransactionCollection;
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
        $this->user = $request->user() ? UserData::from($request->user()) : null;
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
            $uid   = $this->user->id;
            $trxId = $request->integer('transaction_id');

            // Auto get-or-create cart kalau transaction_id tidak dikirim
            if (!$trxId) {
                $cart = $this->transactionService->getOrCreateDraftCart($uid);
                $trxId = $cart->id;
            }

            // Ambil transaksi (role-aware) & authorize penambahan item
            $trx = $this->transactionService->getById($trxId, $this->user);
            $this->authorize('addItem', $trx);

            // Pastikan alamat milik user (defense-in-depth)
            if ($request->filled('user_address_id')) {
                abort_unless(
                    \App\Models\UserAddress::where('id', $request->integer('user_address_id'))
                        ->where('user_id', $uid)->exists(),
                    403,
                    'Address not owned by you.'
                );
            }

            // Payload (unit_amount boleh tidak disertakan; repo akan force dari pickup_fees untuk item pickup)
            $payload = $request->only([
                'item_type',
                'user_address_id',
                'pickup_schedule_id',
                'pickup_fee_id',
                'unit_amount',
                'qty',
                'description',
                'meta',
            ]);

            $item = $this->transactionService->addItemToCart($trxId, $payload, $uid);

            return $this->successResponse(
                data: $item,
                message: 'Item added to cart successfully.'
            );
        } catch (\Throwable $th) {
            [$name, $message, $code, $errors] = $this->normalizeException($th);
            return $this->errorResponse($name, $message, statusCode: $code, errors: $errors);
        }
    }

    public function updateItem(CartRequest $request, int $itemId): JsonResponse
    {
        try {
            $uid = $this->user->id;
            $trxId = $request->integer('transaction_id');

            $trx = $this->transactionService->getById($trxId, $this->user);
            $this->authorize('update', $trx);

            $payload = $request->only([
                'qty',
                'description',
                'unit_amount',
                'user_address_id',
                'pickup_schedule_id',
                'pickup_fee_id',
                'item_type',
            ]);

            $item = $this->transactionService->updateItemInCart($trxId, $itemId, $payload, $uid);

            return $this->successResponse($item, 'Item updated successfully.');
        } catch (\Throwable $th) {
            [$name, $message, $code, $errors] = $this->normalizeException($th);
            return $this->errorResponse($name, $message, statusCode: $code, errors: $errors);
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
