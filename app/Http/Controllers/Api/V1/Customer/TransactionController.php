<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Data\TransactionData;
use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\CartRequest;
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
        $transactions = $this->transactionService->paginate($filters, $pageSize);
        $data = TransactionData::paginatedResponse($transactions);

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

    /** ---------------- Cart Endpoints ---------------- */
    public function cart(): JsonResponse
    {
        // Selalu ambil/buat draft cart milik user
        $cart = $this->transactionService->getOrCreateDraftCart($this->user->id);

        $this->authorize('view', $cart);

        return $this->successResponse(
            data: TransactionData::from($cart),
            message: 'Draft cart retrieved successfully.'
        );
    }

    public function addItem(CartRequest $request): JsonResponse
    {
        try {
            $uid = $this->user->id;

            // Jika transaction_id kosong → auto create cart
            $trxId = (int) $request->input('transaction_id', 0);
            if (!$trxId) {
                $cart  = $this->transactionService->getOrCreateDraftCart($uid);
                $trxId = $cart->id;
            }

            // Authorize terhadap cart milik sendiri & status draft
            $trx = $this->transactionService->getById($trxId, $this->user);
            $this->authorize('addItem', $trx);

            // Payload untuk repo (repo akan enforce fee price, merge dupes, dll)
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

            // Ambil cart terbaru (subtotal/total sudah di-recalc di repo)
            $cart = $this->transactionService->getById($trxId, $this->user);

            $data = TransactionData::from($cart);

            return $this->successResponse(
                data: $data,
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
            $uid   = $this->user->id;
            $trxId = (int) $request->integer('transaction_id');

            // Wajib ada transaction_id untuk update
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

            $cart = $this->transactionService->getById($trxId, $this->user);

            $data = TransactionData::from($cart);

            return $this->successResponse(
                data: $data,
                message: 'Item updated successfully.'
            );
        } catch (\Throwable $th) {
            [$name, $message, $code, $errors] = $this->normalizeException($th);
            return $this->errorResponse($name, $message, statusCode: $code, errors: $errors);
        }
    }

    public function removeItem(Request $request, int $itemId): JsonResponse
    {
        try {
            $uid   = $this->user->id;
            $trxId = (int) $request->integer('transaction_id');

            // Validasi cepat: cart harus draft & milik user
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

            $deleted = $this->transactionService->removeItemFromCart($trxId, $itemId, $uid);

            // $cart = $this->transactionService->getById($trxId, $this->user);

            return $this->successResponse(
                data: ['deleted' => (bool) $deleted],
                message: $deleted ? 'Item removed successfully.' : 'Item not found.'
            );
        } catch (\Throwable $th) {
            [$name, $message, $code, $errors] = $this->normalizeException($th);
            return $this->errorResponse($name, $message, statusCode: $code, errors: $errors);
        }
    }

    public function checkout(Request $request, int $trxId): JsonResponse
    {
        try {
            $uid = $this->user->id;

            // Ambil transaksi & authorize (TransactionPolicy@checkout)
            $trx = $this->transactionService->getById($trxId, $this->user);
            $this->authorize('checkout', $trx);

            // Validasi sederhana: hanya notes, TIDAK ada channel_code di sini
            $payload = $request->validate([
                'notes' => 'nullable|string|max:500',
            ]);
            // Safety: kalau client kirim channel_code, kita abaikan
            unset($payload['channel_code']);

            $pending = $this->transactionService->checkoutCart($trxId, $uid, $payload);

            return $this->successResponse(
                data: TransactionData::from($pending),
                message: 'Checkout prepared successfully (review ready).'
            );
        } catch (\Throwable $th) {
            [$name, $message, $code, $errors] = $this->normalizeException($th);
            return $this->errorResponse($name, $message, statusCode: $code, errors: $errors);
        }
    }
}
