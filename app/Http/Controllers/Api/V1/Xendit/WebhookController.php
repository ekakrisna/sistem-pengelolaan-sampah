<?php

namespace App\Http\Controllers\Api\V1\Xendit;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\PricingService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Xendit\PaymentRequest\PaymentCallback;

class WebhookController extends Controller
{
    use ApiResponse;

    public function __construct(protected PricingService $pricingService) {}

    public function handle(Request $request)
    {
        // 1) Verify token
        $tokenHeader   = $request->header('x-callback-token');
        $expectedToken = config('services.xendit.callback_token');

        if (blank($expectedToken) || $tokenHeader !== $expectedToken) {
            Log::warning('[XenditWebhook] Invalid callback token', ['provided' => $tokenHeader ?? '(none)']);
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // 2) Normalize payload
        $incoming = $request->all();
        $payload  = $incoming['payload'] ?? $incoming;
        Log::info('[XenditWebhook] Incoming payload', ['payload' => $payload]);

        // === NEW: Try parse as PaymentCallback (Payment Request) ===
        $cb = null;
        try {
            $cb = new PaymentCallback($payload);
        } catch (\Throwable $e) {
            // Ignore—means this webhook isn't a PaymentRequest callback
        }

        // 3) Ekstrak external/reference id
        if ($cb) {
            // Payment Request callback — gunakan reference_id dari callback
            $externalId = $cb['data']['reference_id'] ?? null;
        } else {
            // Fallback ke parser lama (qr.payment, VA, retail, dsb)
            $externalId = $this->extractExternalId($payload);
        }

        if (!$externalId) {
            Log::warning('[XenditWebhook] external/reference id not found');
            return $this->errorResponse(
                name: 'Error::XenditWebhook::ExternalIdNotFound',
                message: 'external/reference id not found',
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // 4) Find payment by external_id (untuk PR, kita simpan PR id di external_id ketika create)
        /** @var Payment|null $payment */
        $payment = Payment::where('external_id', $externalId)->first();
        if (!$payment) {
            // Beberapa integrasi menyimpan external_id = reference_id — coba fallback
            $payment = Payment::where('external_id', $payload['data']['payment_request_id'] ?? '')->first();
        }
        if (!$payment) {
            Log::warning('[XenditWebhook] Payment not found', ['external_id' => $externalId]);
            return $this->errorResponse(
                name: 'Error::XenditWebhook::PaymentNotFound',
                message: 'Payment not found for external_id',
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // 5) pickup_id — PaymentRequest menaruh di data.metadata
        $pickupId = null;
        if ($cb) {
            $pickupId = $cb['data']['metadata']['pickup_id'] ?? null;
        }
        if (!$pickupId) {
            // Fallback lama
            $pickupId =
                $this->get($payload, 'metadata.pickup_id') ??
                $this->get($payload, 'data.metadata.pickup_id') ??
                $this->get($payload, 'qr_code.metadata.pickup_id') ??
                ($payment->xendit_data['metadata']['pickup_id'] ?? null) ??
                ($this->get($payment->xendit_data ?? [], 'payload.data.metadata.pickup_id') ?? null);
        }
        if (!$pickupId && preg_match('/-p(?P<pid>\d+)$/', (string) $externalId, $m)) {
            $pickupId = (int) $m['pid'];
        }
        if (!$pickupId) {
            Log::error('[XenditWebhook] pickup_id missing', ['external_id' => $externalId]);
            return $this->errorResponse(
                name: 'Error::XenditWebhook::PickupIdMissing',
                message: 'pickup_id is required in metadata',
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // 6) Map status
        $newStatus = $cb
            ? $this->mapPaymentCallbackStatus($cb['data']['status'] ?? '')
            : $this->mapStatus($payload);

        if (!$newStatus) {
            Log::info('[XenditWebhook] No actionable status', ['external_id' => $externalId]);
            return $this->errorResponse(
                name: 'Error::XenditWebhook::NoActionableStatus',
                message: 'No actionable status',
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // 7) Idempotent short-circuit
        if ($payment->status === 'paid' && $newStatus === 'paid' && $payment->transaction) {
            return $this->successResponse(message: 'Already paid');
        }

        // 8) Update + (if paid) create transaction/items
        DB::transaction(function () use ($payment, $incoming, $payload, $cb, $newStatus, $pickupId) {
            // Merge xendit_data + log last_webhook
            $xd = $payment->xendit_data ?? [];
            $xd['last_webhook'] = [
                'received_at' => now()->toIso8601String(),
                'payload'     => $incoming,
            ];

            // Untuk PaymentCallback, simpan ringkasan penting
            if ($cb) {
                $xd['payment_callback'] = [
                    'event'      => $cb['event'] ?? null,
                    'status'     => $cb['data']['status'] ?? null,
                    'pr_id'      => $cb['data']['payment_request_id'] ?? null,
                    'ref_id'     => $cb['data']['reference_id'] ?? null,
                    'method'     => $cb['data']['payment_method']['type'] ?? null,
                ];
            }

            $update = [
                'status'      => $newStatus,
                'xendit_data' => $xd,
            ];
            if ($newStatus === 'paid' && empty($payment->paid_at)) {
                $update['paid_at'] = Carbon::now();
            }
            $payment->update($update);

            // Buat transaction+items kalau belum ada dan status paid
            if ($newStatus === 'paid' && !$payment->transaction) {
                // Draft items dari metadata (PR)
                $draft = $cb
                    ? ($cb['data']['metadata']['draft_items'] ?? [])
                    : ($payment->xendit_data['metadata']['draft_items'] ?? []);

                // Fallback: coba rebuild dari katalog jika kosong
                if (empty($draft)) {
                    try {
                        $result = $this->pricingService->buildDraftForPickup((int)$pickupId, 1);
                        $draft  = $result['items'] ?? [];
                    } catch (\Throwable $e) {
                        Log::warning('[XenditWebhook] Draft rebuild failed, fallback amount', ['e' => $e->getMessage()]);
                        $draft = [];
                    }
                }

                $trx = Transaction::create([
                    'payment_id'  => $payment->id,
                    'pickup_id'   => (int) $pickupId,
                    'total'       => 0,
                    'description' => 'Auto-created via Xendit webhook',
                ]);

                $grand = '0.00';
                foreach ($draft as $row) {
                    $unit  = (string)($row['unit_amount'] ?? '0.00');
                    $qty   = (int)($row['qty'] ?? 1);
                    $line  = bcmul($unit, (string)$qty, 2);
                    $grand = bcadd($grand, $line, 2);

                    $trx->items()->create([
                        'pickup_fee_id' => $row['pickup_fee_id'] ?? null,
                        'description'   => $row['description'] ?? null,
                        'unit_amount'   => $unit,
                        'qty'           => $qty,
                        'line_total'    => $line,
                        'meta'          => $row['meta'] ?? null,
                    ]);
                }

                if (empty($draft)) {
                    $grand = number_format((float)$payment->amount, 2, '.', '');
                }
                $trx->update(['total' => $grand]);
            }
        });

        return $this->successResponse(message: 'OK');
    }

    // ---------- Helpers lama + tambahan ----------
    protected function get(array $arr, string $path, $default = null)
    {
        $cur = $arr;
        foreach (explode('.', $path) as $seg) {
            if (is_array($cur) && array_key_exists($seg, $cur)) {
                $cur = $cur[$seg];
            } else {
                return $default;
            }
        }
        return $cur;
    }

    protected function extractExternalId(array $p): ?string
    {
        if ($this->get($p, 'data.reference_id')) return $this->get($p, 'data.reference_id');
        if ($this->get($p, 'qr_code.external_id')) return $this->get($p, 'qr_code.external_id');
        if (!empty($p['reference_id'])) return $p['reference_id'];
        if (!empty($p['external_id']))  return $p['external_id'];
        if ($this->get($p, 'data.external_id')) return $this->get($p, 'data.external_id');
        if (!empty($p['callback_virtual_account_id']) && !empty($p['external_id'])) return $p['external_id'];
        if (!empty($p['payment_code']) && !empty($p['external_id'])) return $p['external_id'];
        return null;
    }

    protected function mapStatus(array $p): ?string
    {
        if (!empty($p['status'])) return $this->normalizeStatus($p['status']);
        if ($this->get($p, 'data.status')) return $this->normalizeStatus($this->get($p, 'data.status'));
        if (!empty($p['payment_id'])) return 'paid';
        return null;
    }

    protected function mapPaymentCallbackStatus(string $status): ?string
    {
        // status di PaymentCallback: SUCCEEDED / FAILED / PROCESSING / etc
        return $this->normalizeStatus($status);
    }

    protected function normalizeStatus(string $status): ?string
    {
        return match (strtoupper($status)) {
            'COMPLETED', 'SUCCEEDED', 'PAID', 'SETTLED' => 'paid',
            'PROCESSING', 'SETTLING', 'REQUIRES_ACTION', 'PENDING', 'ACTIVE' => 'pending',
            'FAILED', 'EXPIRED', 'CANCELLED', 'VOIDED' => 'failed',
            default => null,
        };
    }
}
