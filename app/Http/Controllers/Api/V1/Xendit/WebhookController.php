<?php

namespace App\Http\Controllers\Api\V1\Xendit;

use App\Enums\StatusPaymentEnum;
use App\Enums\StatusTransactionEnum;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentSplitRoute;
use App\Models\Transaction;
use App\Services\Xendits\Webhook\XenditWebhookService;
use App\Traits\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected XenditWebhookService $webhooks
    ) {}

    public function payment(Request $request): JsonResponse
    {
        try {
            $result = $this->webhooks->handle($request, function (array $payload) {
                $event = strtolower((string) ($payload['event'] ?? ''));

                // Hanya proses event terkait payments API
                if (!str_starts_with($event, 'payment')) {
                    return true;
                }

                $data        = $payload['data'] ?? [];
                $prId        = $data['payment_request_id'] ?? null;
                $referenceId = $data['reference_id'] ?? null;

                // Temukan payment lokal
                $payment = $this->findPayment($prId, $referenceId);
                if (!$payment) {
                    Log::warning('Webhook payment not matched', compact('event', 'prId', 'referenceId'));
                    return true;
                }

                // Idempoten: kalau status final & sama, lewati
                $incoming = $this->mapIncomingStatus($event, $data);
                if ($this->isNoOp($payment->status, $incoming->paymentStatus)) {
                    return true;
                }

                // Timestamps
                $paidAt = $this->resolvePaidAt($data);
                $expiresAt = $this->resolveExpiresAt($data);

                // Update atomik
                DB::transaction(function () use ($payment, $incoming, $paidAt, $expiresAt, $payload) {
                    // Update payment
                    $updates = [
                        'status'      => $incoming->paymentStatus->value,
                        'xendit_data' => $payload,
                    ];
                    if ($paidAt && $incoming->paymentStatus === StatusPaymentEnum::SUCCEEDED) {
                        $updates['paid_at'] = $paidAt;
                    }
                    if ($expiresAt && $incoming->paymentStatus === StatusPaymentEnum::EXPIRED) {
                        $updates['expires_at'] = $expiresAt;
                    }
                    $payment->update($updates);

                    // Update transaction
                    /** @var Transaction|null $trx */
                    $trx = $payment->transaction()->lockForUpdate()->first();
                    if ($trx) {
                        $trxUpdate = ['status' => $incoming->transactionStatus->value];
                        // Optional: saat sukses bisa clear expires_at
                        if ($incoming->transactionStatus === StatusTransactionEnum::PAID) {
                            $trxUpdate['expires_at'] = null;
                        }
                        if ($expiresAt && $incoming->transactionStatus === StatusTransactionEnum::EXPIRED) {
                            $trxUpdate['expires_at'] = $expiresAt;
                        }
                        $trx->update($trxUpdate);
                    }
                });

                return true;
            });

            return $this->successResponse($result, 'Webhook processed.');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeException($th);
            return $this->errorResponse(name: $name, message: $message, statusCode: $code);
        }
    }

    public function split(Request $request): JsonResponse
    {
        try {
            $result = $this->webhooks->handle($request, function (array $payload) {
                $event = strtolower((string) ($payload['event'] ?? ''));
                Log::info('split-webhook:received', ['event' => $event, 'id' => data_get($payload, 'data.id')]);

                if (str_starts_with($event, 'split.')) {
                    $this->handleSplitEvent($event, $payload);
                }
                return true;
            });

            return $this->successResponse($result, 'Webhook processed.');
        } catch (\Throwable $th) {
            [$name, $message, $code] = $this->normalizeException($th);
            return $this->errorResponse(name: $name, message: $message, statusCode: $code);
        }
    }


    /** ---------------- Helpers ---------------- */
    protected function findPayment(?string $paymentRequestId, ?string $referenceId): ?Payment
    {
        if ($paymentRequestId) {
            $p = Payment::query()
                ->where('xendit_payment_request_id', $paymentRequestId)
                ->latest('id')
                ->first();
            if ($p) return $p;
        }

        if ($referenceId) {
            return Payment::query()
                ->where('reference_id', $referenceId)
                ->latest('id')
                ->first();
        }

        return null;
    }

    /**
     * Map event/status Xendit → status kita.
     */
    protected function mapIncomingStatus(string $event, array $data): object
    {
        $statusRaw = strtoupper((string) ($data['status'] ?? ''));

        // Default
        $payment = StatusPaymentEnum::INITIATED;
        $trx     = StatusTransactionEnum::PENDING;

        if (in_array($event, ['payment.succeeded', 'payment.capture'], true) || $statusRaw === 'SUCCEEDED') {
            $payment = StatusPaymentEnum::SUCCEEDED;
            $trx     = StatusTransactionEnum::PAID;
        } elseif (in_array($event, ['payment.failed', 'payment.failure'], true) || $statusRaw === 'FAILED') {
            $payment = StatusPaymentEnum::FAILED;
            $trx     = StatusTransactionEnum::FAILED;
        } elseif (in_array($event, ['payment.canceled', 'payment.cancelled'], true) || $statusRaw === 'CANCELED') {
            $payment = StatusPaymentEnum::CANCELED;
            $trx     = StatusTransactionEnum::CANCELED;
        } elseif ($event === 'payment_request.expiry' || $statusRaw === 'EXPIRED') {
            $payment = StatusPaymentEnum::EXPIRED;
            $trx     = StatusTransactionEnum::EXPIRED;
        }

        return (object) [
            'paymentStatus'     => $payment,
            'transactionStatus' => $trx,
        ];
    }

    protected function isNoOp(string $currentPaymentStatus, StatusPaymentEnum $incoming): bool
    {
        // Jika sudah status final yang sama, anggap no-op
        $finals = [
            StatusPaymentEnum::SUCCEEDED->value,
            StatusPaymentEnum::EXPIRED->value,
            StatusPaymentEnum::FAILED->value,
            StatusPaymentEnum::CANCELED->value,
            StatusPaymentEnum::REFUNDED->value,
        ];
        return $currentPaymentStatus === $incoming->value
            && in_array($incoming->value, $finals, true);
    }

    protected function resolvePaidAt(array $data): ?CarbonImmutable
    {
        $captures  = Arr::get($data, 'captures', []);
        $paidAtIso = $captures[0]['capture_timestamp']
            ?? $data['updated']
            ?? $data['created']
            ?? null;

        return $paidAtIso ? CarbonImmutable::parse($paidAtIso) : null;
    }

    protected function resolveExpiresAt(array $data): ?CarbonImmutable
    {
        $expiresAtIso = Arr::get($data, 'channel_properties.expires_at');
        return $expiresAtIso ? CarbonImmutable::parse($expiresAtIso) : null;
    }

    protected function handleSplitEvent(string $event, array $payload): void
    {
        $data     = $payload['data'] ?? [];
        $routeRef = (string) ($data['reference_id'] ?? '');
        $status   = strtoupper((string) ($data['status'] ?? ''));
        $settledAt = CarbonImmutable::now();

        if ($routeRef === '') {
            // Tanpa reference_id, sulit memetakan baris yang tepat → log & keluar aman
            Log::warning('split.payment missing route reference_id', ['payload' => $payload]);
            return;
        }

        // Cari baris route lewat reference_id (unik)
        /** @var PaymentSplitRoute|null $route */
        $route = PaymentSplitRoute::query()
            ->where('reference_id', $routeRef)
            ->first();

        if (!$route) {
            // fallback long-shot: split_rule_id + destination_account_id + amount (kurang direkomendasikan)
            $route = PaymentSplitRoute::query()
                ->when(!empty($data['split_rule_id']), fn($q) => $q->where('split_rule_id', $data['split_rule_id']))
                ->when(!empty($data['destination_account_id']), fn($q) => $q->where('destination_account_id', $data['destination_account_id']))
                ->first();
        }

        if (!$route) {
            Log::warning('split.payment route not matched', [
                'reference_id' => $routeRef,
                'split_rule_id' => $data['split_rule_id'] ?? null,
                'dest'         => $data['destination_account_id'] ?? null,
            ]);
            return;
        }

        // Map status split → status internal route
        [$newStatus, $setSettledAt] = $this->mapSplitRouteStatus($status, $settledAt);

        // Update satu baris route
        $route->update([
            'status'     => $newStatus,
            'settled_at' => $setSettledAt,
            'meta'       => array_merge((array) $route->meta ?? [], [
                'split_webhook' => $payload,
            ]),
        ]);

        // OPTIONAL: jika semua route untuk payment ini sudah "settled/failed", kamu bisa menandai sesuatu di Payment
        // namun biasanya payment->SUCCEEDED sudah lebih dulu ditandai oleh event payment.<...>
    }

    /**
     * Map status dari split webhook ke status internal payment_split_routes.
     * - COMPLETED  => settled
     * - FAILED     => failed
     * - PENDING    => applied (atau biarkan planned → tapi kita anggap applied)
     */
    protected function mapSplitRouteStatus(string $statusRaw, CarbonImmutable $now): array
    {
        return match ($statusRaw) {
            'COMPLETED' => ['settled', $now],
            'FAILED'    => ['failed', null],
            'PENDING'   => ['applied', null],
            default     => ['applied', null], // default aman
        };
    }
}
