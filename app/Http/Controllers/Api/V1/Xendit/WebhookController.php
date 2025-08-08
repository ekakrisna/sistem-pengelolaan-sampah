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

class WebhookController extends Controller
{
    use ApiResponse;

    protected PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    public function handle(Request $request)
    {
        // 1) Verify callback token (ACK cepat kalau salah)
        $tokenHeader   = $request->header('x-callback-token');
        $expectedToken = config('services.xendit.callback_token');

        if (blank($expectedToken) || $tokenHeader !== $expectedToken) {
            Log::warning('[XenditWebhook] Invalid callback token', ['provided' => $tokenHeader]);
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // 2) Normalize/unwrap payload
        $incoming = $request->all();
        $payload  = $incoming['payload'] ?? $incoming;

        Log::info('[XenditWebhook] Incoming', ['payload' => $payload]);

        // 3) Ekstrak external/reference id
        $externalId = $this->extractExternalId($payload);
        if (!$externalId) {
            Log::warning('[XenditWebhook] external/reference id not found');
            return $this->errorResponse(
                name: 'Error::XenditWebhook::ExternalIdNotFound',
                message: 'external/reference id not found',
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // 4) Ambil Payment
        /** @var Payment|null $payment */
        $payment = Payment::where('external_id', $externalId)->first();
        if (!$payment) {
            Log::warning('[XenditWebhook] Payment not found', ['external_id' => $externalId]);
            return $this->errorResponse(
                name: 'Error::XenditWebhook::PaymentNotFound',
                message: 'Payment not found for external_id',
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // 5) pickup_id REQUIRED — cari dari beberapa sumber + fallback dari external_id
        $pickupId =
            $this->get($payload, 'metadata.pickup_id') ??
            $this->get($payload, 'data.metadata.pickup_id') ??
            $this->get($payload, 'qr_code.metadata.pickup_id') ??
            ($payment->xendit_data['metadata']['pickup_id'] ?? null) ??
            ($this->get($payment->xendit_data ?? [], 'payload.data.metadata.pickup_id') ?? null);

        // Fallback parse dari external_id format: ...-p{ID}
        if (!$pickupId && preg_match('/-p(?P<pid>\d+)$/', (string) $externalId, $m)) {
            $pickupId = (int) $m['pid'];
        }

        if (!$pickupId) {
            Log::error('[XenditWebhook] pickup_id missing', [
                'external_id' => $externalId,
                'payload_metadata' => [
                    'metadata'         => $this->get($payload, 'metadata'),
                    'data.metadata'    => $this->get($payload, 'data.metadata'),
                    'qr_code.metadata' => $this->get($payload, 'qr_code.metadata'),
                ],
            ]);
            return $this->errorResponse(
                name: 'Error::XenditWebhook::PickupIdMissing',
                message: 'pickup_id is required in metadata',
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // 6) Normalisasi status
        $newStatus = $this->mapStatus($payload);
        if (!$newStatus) {
            Log::info('[XenditWebhook] No actionable status', ['external_id' => $externalId]);
            return $this->errorResponse(
                name: 'Error::XenditWebhook::NoActionableStatus',
                message: 'No actionable status',
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // 7) Idempotent early return
        if ($payment->status === 'paid' && $newStatus === 'paid') {
            if ($payment->transaction) {
                Log::info('[XenditWebhook] Already paid + transaction exists', ['external_id' => $externalId]);
                return $this->successResponse(message: 'Already paid');
            }
            // status paid tapi belum ada transaction? lanjut buat di bawah
        }

        // 8) Update Payment & (jika paid) buat transaction + items
        DB::transaction(function () use ($payment, $incoming, $newStatus, $pickupId) {
            // Merge xendit_data lama + log last_webhook
            $xenditData = $payment->xendit_data ?? [];
            $xenditData['last_webhook'] = [
                'received_at' => now()->toIso8601String(),
                'payload'     => $incoming,
            ];

            $update = [
                'status'      => $newStatus,
                'xendit_data' => $xenditData,
            ];

            if ($newStatus === 'paid' && empty($payment->paid_at)) {
                $update['paid_at'] = Carbon::now();
            }

            $payment->update($update);

            Log::info('[XenditWebhook] Payment updated', [
                'data' => $payment->toArray(),
            ]);

            // Saat status = paid atau settling, kita anggap kasir sudah terima uang
            if (in_array($newStatus, ['paid', 'settling'], true) && !$payment->transaction) {
                $draft = $payment->xendit_data['metadata']['draft_items'] ?? [];
                log::info('[XenditWebhook] Draft items', [
                    'external_id' => $payment->external_id,
                    'draft'       => $draft,
                ]);
                // Fallback: rebuild dari katalog kalau draft kosong
                if (empty($draft)) {
                    try {
                        $qty = (int) ($payment->xendit_data['metadata']['qty'] ?? 1);
                        $result = $this->pricingService->buildDraftForPickup((int) $pickupId, $qty);
                        Log::info('[XenditWebhook] Draft rebuilt', [
                            'external_id' => $payment->external_id,
                            'result'      => $result,
                        ]);
                        $draft = $result['items'] ?? [];
                    } catch (\Throwable $e) {
                        Log::warning('[XenditWebhook] Draft rebuild failed, using payment amount only', [
                            'external_id' => $payment->external_id,
                            'error'       => $e->getMessage(),
                        ]);
                        $draft = [];
                    }
                }

                $trx = Transaction::create([
                    'payment_id'  => $payment->id,
                    'pickup_id'   => (int) $pickupId,
                    'total'       => 0, // akan di-update setelah sum items
                    'description' => 'Auto-created via Xendit webhook',
                ]);

                $grand = '0.00';

                // Insert items jika ada draft
                foreach ($draft as $row) {
                    $unit   = (string) ($row['unit_amount'] ?? '0.00');
                    $qty    = (int) ($row['qty'] ?? 1);
                    $line   = bcmul($unit, (string) $qty, 2);
                    $grand  = bcadd($grand, $line, 2);

                    $trx->items()->create([
                        'pickup_fee_id' => $row['pickup_fee_id'] ?? null,
                        'description'   => $row['description']   ?? null,
                        'unit_amount'   => $unit,
                        'qty'           => $qty,
                        'line_total'    => $line,
                        'meta'          => $row['meta'] ?? null,
                    ]);
                }

                // Kalau gak ada draft item, fallback total = payment->amount
                if (empty($draft)) {
                    $grand = number_format((float) $payment->amount, 2, '.', '');
                }

                $trx->update(['total' => $grand]);

                // (Opsional) rekonsiliasi: log selisih jika grand != payment->amount
                $paymentAmount = number_format((float) $payment->amount, 2, '.', '');
                if (bccomp($grand, $paymentAmount, 2) !== 0) {
                    Log::warning('[XenditWebhook] Total mismatch (transaction vs payment)', [
                        'external_id' => $payment->external_id,
                        'trx_total'   => $grand,
                        'pay_amount'  => $paymentAmount,
                    ]);
                }
            }
        });

        Log::info('[XenditWebhook] Updated', [
            'external_id' => $externalId,
            'status'      => $newStatus,
            'pickup_id'   => $pickupId,
        ]);

        return $this->successResponse(message: 'OK');
    }

    /**
     * Safe dot-get helper
     */
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

    /**
     * Ekstrak external/reference id untuk semua variasi payload
     */
    protected function extractExternalId(array $p): ?string
    {
        // E‑Wallet capture payload (contoh kamu): data.reference_id
        if ($this->get($p, 'data.reference_id')) {
            return $this->get($p, 'data.reference_id');
        }

        // QR Code terbaru (event: qr.payment): data.reference_id
        if ($this->get($p, 'data.reference_id')) {
            return $this->get($p, 'data.reference_id');
        }

        // QR Code variasi lama: qr_code.external_id
        if ($this->get($p, 'qr_code.external_id')) {
            return $this->get($p, 'qr_code.external_id');
        }

        // General fallbacks
        if (!empty($p['reference_id'])) return $p['reference_id'];
        if (!empty($p['external_id']))  return $p['external_id'];
        if ($this->get($p, 'data.external_id')) return $this->get($p, 'data.external_id');

        // VA
        if (!empty($p['callback_virtual_account_id']) && !empty($p['external_id'])) {
            return $p['external_id'];
        }

        // Retail outlet
        if (!empty($p['payment_code']) && !empty($p['external_id'])) {
            return $p['external_id'];
        }

        return null;
    }

    /**
     * Normalisasi status → paid | pending | failed | null
     */
    protected function mapStatus(array $p): ?string
    {
        // 1) status di root (beberapa event)
        if (!empty($p['status'])) {
            return $this->normalizeStatus($p['status']);
        }

        // 2) status di data.status (ewallet.capture / qr.payment)
        if ($this->get($p, 'data.status')) {
            return $this->normalizeStatus($this->get($p, 'data.status'));
        }

        // 3) VA paid tanpa status eksplisit
        if (!empty($p['payment_id'])) {
            return 'paid';
        }

        return null;
    }

    protected function normalizeStatus(string $status): ?string
    {
        return match (strtoupper($status)) {
            'COMPLETED', 'SUCCEEDED', 'PAID', 'SETTLED' => 'paid',
            'SETTLING'  => 'settling',
            'PENDING', 'ACTIVE' => 'pending',
            'FAILED', 'EXPIRED', 'CANCELLED', 'VOIDED' => 'failed',
            default => null,
        };
    }
}
