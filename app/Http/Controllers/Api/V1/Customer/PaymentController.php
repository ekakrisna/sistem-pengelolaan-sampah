<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Data\CreatePaymentData;
use App\Data\UserData;
use App\Enums\UserEnum;
use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Services\TransactionService;
use App\Services\Xendit\Managers\PaymentRequestManager;
use App\Services\Xendit\Queriers\PaymentRequestQueryService;
use App\Traits\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    use ApiResponse;

    protected $user;

    public function __construct(
        protected PaymentRequestManager $xenditPaymentManager,
        protected PaymentRequestQueryService $paymentRequestQueryService,
        protected Request $request,
        protected PaymentService $paymentService,
        protected TransactionService $transactionService
    ) {
        $this->user = UserData::from($request->user());
    }

    /**
     * Create a new payment request (E-Wallet, QRIS, VA, Tokenized)
     */
    public function store(CreatePaymentData $data): JsonResponse
    {
        try {
            $actor = $this->user;

            // $validated = $data->validate(CreatePaymentData::rules());
            $data = CreatePaymentData::from($data)
                ->withDefaults(
                    defaultCurrency: config('service.xendit.currency', 'IDR'),
                    defaultCountry: config('service.xendit.country', 'ID'),
                );

            // (opsional) validasi konsistensi amount vs items
            if ($data->items && ($calc = $data->totalFromItems()) !== null) {
                $calcInt   = (int) round((float) $calc);
                $amountInt = (int) $data->amount;

                if ($calcInt !== $amountInt) {
                    return $this->errorResponse(
                        name: 'Error::Validation',
                        message: "Amount ({$amountInt}) doesn't match items total ({$calcInt})",
                        statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
                    );
                }
            }

            // 1) Create Payment Request (Xendit)
            $pr = $this->xenditPaymentManager->create($data);

            // Siapkan ctx untuk penyimpanan lokal (customer_id ditentukan server)
            $resolvedCustomerId = $this->resolveCustomerId($actor, $data->customer_id);

            // 2) Simpan lokal (Payment + Transaction + Items)
            $ctx = [
                'customer_id'      => $resolvedCustomerId,
                'channel_category' => $data->channel_category->value,
                'currency'         => $data->currency,
                'country'          => $data->country,
                'idempotency_key'  => $data->idempotency_key,
                'for_user_id'      => $data->for_user_id,
                'with_split_rule_id' => $data->with_split_rule_id,
            ];

            [$payment, $transaction] = DB::transaction(function () use ($pr, $ctx, $data) {
                $payment = $this->paymentService->storeFromXendit($pr, $ctx);

                $txExtra = [
                    'pickup_id'   => $data->pickup_id,
                    'description' => $data->transaction_description,
                ];
                $items = $data->items?->toArray() ?? [];

                $transaction = $this->transactionService->createWithItems($payment, $items, $txExtra);
                $payment->load(['transaction.items']);
                return [$payment, $transaction];
            });

            return $this->successResponse(
                data: $payment,
                message: 'Payment request created & stored successfully.'
            );
        } catch (\Throwable $e) {
            report($e);
            return $this->errorResponse(
                name: 'Error::InternalServerError',
                message: $e->getMessage(),
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function show(string $id, Request $request)
    {
        try {
            $forUserId = $request->query('for_user_id');

            $result = $this->xenditPaymentManager->getPaymentRequestById(
                $id,
                $forUserId
            );
            if (empty($result)) {
                return $this->errorResponse(
                    name: 'Error::NotFound',
                    message: 'Payment request not found.',
                    statusCode: Response::HTTP_NOT_FOUND
                );
            }

            return $this->successResponse(
                data: $result,
                message: 'Payment request found.'
            );
        } catch (\Throwable $e) {
            $code = (int) $e->getCode();
            $httpCode = ($code >= 400 && $code < 600) ? $code : Response::HTTP_UNPROCESSABLE_ENTITY;

            return $this->errorResponse(
                name: 'Error::InternalServerError',
                message: $e->getMessage(),
                statusCode: $httpCode
            );
        }
    }

    private function resolveCustomerId(UserData $actor, ?int $payloadCustomerId): ?int
    {
        // super_admin bebas set (atau biarkan null jika memang tidak terkait customer tertentu)
        if ($actor->role === UserEnum::SuperAdmin) {
            return $payloadCustomerId ?? null;
        }

        // customer: harus pakai id dirinya sendiri, ignore payload
        if ($actor->role === UserEnum::Customer) {
            if ($payloadCustomerId !== null && $payloadCustomerId !== $actor->id) {
                throw new AuthorizationException('You are not allowed to change customer_id.');
            }
            return $actor->id;
        }

        // role lain (admin/petugas): tidak boleh menetapkan customer_id secara manual
        // -> kalau bisnis kamu mau izinkan, tulis aturanmu di sini.
        if ($payloadCustomerId !== null) {
            throw new AuthorizationException('You are not allowed to set customer_id.');
        }

        return null;
    }
}
