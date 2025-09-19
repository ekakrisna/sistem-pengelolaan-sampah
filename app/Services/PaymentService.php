<?php

namespace App\Services;

use App\Data\ChannelExpiry;
use App\Data\SplitRule\FeeConfigData;
use App\Data\UserData;
use App\Data\Xendit\Common\ChannelPropsData;
use App\Data\Xendit\PaymentRequest\PaymentsApiPayData;
use App\Enums\XenditStatusPaymentEnum as XPR;
use App\Enums\Xendit\Common\CaptureMethod;
use App\Enums\Xendit\Common\ChannelCode;
use App\Enums\Xendit\Common\Country;
use App\Enums\Xendit\Common\Currency;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Repositories\PaymentRepository;
use App\Repositories\TransactionRepository;
use App\Services\Xendits\PaymentRequest\PaymentPayService;
use App\Services\Xendits\PaymentRequest\PaymentRequestService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(
        protected PaymentRepository $paymentRepository,
        protected TransactionRepository $transactionRepository,
        protected PaymentPayService $xenditPaymentPay,
        protected PaymentRequestService $paymentRequestService
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->transactionRepository = $transactionRepository;
        $this->xenditPaymentPay = $xenditPaymentPay;
        $this->paymentRequestService = $paymentRequestService;
    }


    public function getAll(?UserData $user = null)
    {
        return $this->paymentRepository->all($user, $user);
    }


    public function getById(int $id, ?UserData $user = null): Payment
    {
        return $this->paymentRepository->getById($id, $user);
    }


    public function save(array $data, ?UserData $user = null)
    {
        return $this->paymentRepository->save($data, $user);
    }

    public function update(array $data, int $id, ?UserData $user = null)
    {
        DB::beginTransaction();
        try {
            $paymentRepository = $this->paymentRepository->update($data, $id, $user);
            DB::commit();
            return $paymentRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    public function deleteById(int $id, ?UserData $user = null)
    {
        DB::beginTransaction();
        try {
            $paymentRepository = $this->paymentRepository->delete($id, $user);
            DB::commit();
            return $paymentRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    public function paginate(array $filters, int $pageSize = 10, ?UserData $user = null)
    {
        return $this->paymentRepository->paginateWithFilters($filters, $pageSize, $user);
    }

    public function createPaymentRequest(int $transactionId, UserData $user, array $request): array
    {
        return DB::transaction(function () use ($transactionId, $user, $request) {

            // 1) Lock draft transaksi milik user + normalisasi + validasi
            $trx = $this->transactionRepository->lockDraftForUser($transactionId, $user->id);
            $this->transactionRepository->normalizeAndRecalc($trx);
            $this->transactionRepository->assertOwnershipAndCompatibility($trx);

            if ($trx->transaction_items()->count() === 0) {
                throw new \InvalidArgumentException('Cannot create payment for empty cart.');
            }

            // 2) Pastikan nomor transaksi ada
            $this->transactionRepository->ensureNumber($trx);

            // 3) Build DTO sesuai definisi PaymentsApiPayData
            $channel = ChannelCode::from($request['channel_code']);
            $propsArr        = $request['channel_properties'] ?? [];

            // Prioritas: kalau client sudah kirim expires_at, pakai itu; else pakai default per channel
            if (empty($propsArr['expires_at'])) {
                /** @var CarbonImmutable $expiresAt */
                $expiresAt = ChannelExpiry::defaultExpiresAt($channel);
                $propsArr['expires_at'] = $expiresAt->toIso8601String(); // RFC 3339
            } else {
                $expiresAt = CarbonImmutable::parse($propsArr['expires_at']);
            }

            // Simpan di transaksi juga (biar konsisten internal)
            $trx->expires_at = $expiresAt;
            $trx->due_at     = $trx->due_at ?? $expiresAt;
            $trx->save();

            // Jika client belum kirim display_name, pakai yang di transaksi
            if (empty($propsArr['display_name'])) {
                $propsArr['display_name'] = $trx->customer->name;
            }

            // Jika client belum kirim mobile_number, pakai yang di transaksi untuk E-Wallet (OVO, Gopay, etc)
            if (empty($propsArr['account_mobile_number'])) {
                $propsArr['account_mobile_number'] = $trx->customer->phone;
            }

            // Jika client belum kirim email, pakai yang di transaksi untuk E-Wallet (OVO, Gopay, etc)
            if (empty($propsArr['account_email'])) {
                $propsArr['account_email'] = $trx->customer->email;
            }

            $cleanChannelProps = ChannelPropsData::buildForChannel(
                $channel,
                $propsArr,
                $trx,
                $expiresAt
            );

            // 4) Build DTO strict
            $channelProps = ChannelPropsData::from($cleanChannelProps)->toArray();

            // (Opsional) siapkan data customer bila ingin kirim ke Xendit Customer object
            // $individualDetail = CustomerIndividualDetailData::from([
            //     'given_names' => $user->name,
            // ]);

            // $customerDto = CustomerData::from([
            //     'reference_id' => (string) $user->id,
            //     'type'         => CustomerType::INDIVIDUAL,
            //     'mobile_number' => $user->phone ?? '',
            //     'email'        => $user->email ?? null,
            //     'individual_detail' => $individualDetail->toArray()
            // ])->toArray();

            // (Opsional) items — jika ingin mengirim itemized detail ke Xendit
            // $itemsDto = null; // atau ItemData::collection([...]) bila definisi ItemData mendukung collection

            $dto = PaymentsApiPayData::from([
                'reference_id'     => $trx->number,
                'country'          => Country::ID,
                'currency'         => Currency::IDR,
                'request_amount'   => (float) $trx->total,
                // 'customer'         => $customerDto,
                'capture_method'   => CaptureMethod::AUTOMATIC,
                'channel_code'     => $channel,
                'channel_properties' => $channelProps,
                'description'      => $trx->description,
                'metadata'         => $request['metadata'] ?? null,
                'items'            => null
            ]);

            // Idempotency key (sudah kamu implement)
            $idempKey    = $this->makeIdempotencyKey($trx, $channel, $expiresAt);

            // === NEW: Resolve subaccount & split ===
            // $forUserId   = $this->resolveForUserId($trx, $request);
            [$splitRuleId, $routesPlan] = $this->resolveSplitRuleId($trx, $request);

            // kalau sudah pernah dibuat (retry) => kembalikan existing
            if ($existing = $this->paymentRepository->findByIdempotencyKey($idempKey)) {
                return [
                    'payment'     => $existing->fresh(),
                    'transaction' => $trx->fresh(['transaction_items', 'payments']),
                    'xendit'      => $existing->xendit_data ?? null,
                ];
            }

            // 4) Panggil Xendit (DTO → array payload via toPayload())
            $xenditResp = $this->xenditPaymentPay->create(
                $dto,
                idempotencyKey: $idempKey,
                // forUserId: $forUserId,
                splitRuleId: $splitRuleId
            );

            // 5) Simpan ke payments + update transaksi ke pending (strict by columns)
            $saved = $this->paymentRepository->createFromXenditStrict(
                trx: $trx,
                resp: $xenditResp,
                expiresAt: $expiresAt,
                splitRuleId: $splitRuleId,
                idempotencyKey: $idempKey,
                routesPlan: $routesPlan
            );

            return [
                'payment'     => $saved['payment'],
                'transaction' => $saved['transaction'],
                'xendit'      => $xenditResp,
            ];
        });
    }

    /**
     * Cancel payment (Xendit -> DB). Return [Payment $updated, array $xenditResp]
     */
    public function cancelPayment(Payment $payment): array
    {
        // Validasi lokal basic
        if ($payment->paid_at) {
            throw new InvalidArgumentException('Paid payment cannot be canceled.');
        }
        $prId = $payment->xendit_payment_request_id;
        if (!$prId) {
            throw new InvalidArgumentException('Missing Xendit payment_request_id on this payment.');
        }

        // 0) Ambil status PR dari gateway (source of truth)
        $pr = $this->paymentRequestService->getById($prId);
        $statusRaw = strtoupper((string) ($pr['status'] ?? ''));
        $prStatus  = XPR::tryFrom($statusRaw);

        if (!$prStatus) {
            // status tak dikenal — jangan cancel, lempar error yang jelas
            throw new InvalidArgumentException("Unknown payment request status: {$statusRaw}");
        }

        // 1) Hanya REQUIRES_ACTION & ACCEPTING_PAYMENTS yang dapat di-cancel lewat PR cancel endpoint
        if (in_array($prStatus, [XPR::REQUIRES_ACTION, XPR::ACCEPTING_PAYMENTS], true)) {
            $xCancel = $this->paymentRequestService->cancel($prId);

            $updated = DB::transaction(function () use ($payment, $xCancel) {
                return $this->paymentRepository
                    ->applyCancellationFromGateway($payment, $xCancel, 'cancelled_by_user');
            });

            return [$updated, $xCancel];
        }

        // 2) Kalau sudah CANCELED/EXPIRED/FAILED di gateway → sinkronisasi lokal saja (no-op cancel)
        if (in_array($prStatus, [XPR::CANCELED, XPR::EXPIRED, XPR::FAILED], true)) {
            $updated = DB::transaction(function () use ($payment, $pr) {
                return $this->paymentRepository
                    ->applyCancellationFromGateway($payment, $pr, 'sync_from_gateway');
            });
            return [$updated, $pr];
        }

        // 3) AUTHORIZED → void payment (bukan cancel PR)
        if ($prStatus === XPR::AUTHORIZED) {
            if (!$this->paymentRequestService) {
                throw new InvalidArgumentException('PaymentsService not configured for voiding AUTHORIZED payments.');
            }

            // Cari payment yang AUTHORIZED dari array PR['payments'] (jika ada)
            $payments = $pr['payments'] ?? [];
            $voidResp = null;

            foreach ($payments as $p) {
                $pid = $p['id'] ?? null;
                $ps  = strtoupper((string)($p['status'] ?? ''));
                if ($pid && $ps === XPR::AUTHORIZED->value) {
                    $voidResp = $this->paymentRequestService->cancel($pid); // void
                    break;
                }
            }

            // Refresh PR setelah void
            $refreshed = $this->paymentRequestService->getById($prId);

            $updated = DB::transaction(function () use ($payment, $refreshed) {
                return $this->paymentRepository
                    ->applyCancellationFromGateway($payment, $refreshed, 'void_authorization');
            });

            return [$updated, ['payment_cancel' => $voidResp, 'payment_request' => $refreshed]];
        }

        // 4) SUCCEEDED → tidak bisa cancel (sudah terbayar)
        if ($prStatus === XPR::SUCCEEDED) {
            throw new InvalidArgumentException('Payment request already paid; cannot cancel.');
        }

        // 5) Status lain (yang tidak didukung cancel oleh API)
        throw new InvalidArgumentException(
            "Cannot cancel payment request in status {$prStatus->value}. Only REQUIRES_ACTION or ACCEPTING_PAYMENTS can be canceled."
        );
    }

    protected function makeIdempotencyKey(
        Transaction $trx,
        ChannelCode $channel,
        CarbonInterface $expiresAt
    ): string {
        return implode(':', [
            'PR',
            $trx->number ?: ('TRX' . $trx->id),
            $channel->value,
            (string) (int) round($trx->total * 100),
            (string) $expiresAt->getTimestamp(),
        ]);
    }

    protected function resolveForUserId(TransactionItem $it): ?string
    {
        // Prioritas lewat pickup_fee -> waste_type -> admin
        if ($it->relationLoaded('pickup_fee') && $it->pickup_fee && $it->pickup_fee->relationLoaded('waste_type') && $it->pickup_fee->waste_type) {
            return $it->pickup_fee->waste_type->admin ?? null;
        }

        // Atau langsung lewat schedule -> admin
        if ($it->relationLoaded('pickup_schedule') && $it->pickup_schedule) {
            return $it->pickup_schedule->admin ?? null;
        }

        // Jika relasi belum diload, load minimal
        $it->loadMissing('pickup_fee.waste_type.admin', 'pickup_schedule.admin');

        if ($it->pickup_fee && $it->pickup_fee->waste_type && $it->pickup_fee->waste_type->admin) {
            return $it->pickup_fee->waste_type->admin;
        }

        if ($it->pickup_schedule && $it->pickup_schedule->admin) {
            return $it->pickup_schedule->admin;
        }

        return null;
    }

    /**
     * Bangun Split Rule di Xendit berbasis isi cart:
     * - route percent ke masing-masing admin proporsional thd nominal per-admin
     * - opsional: platform fee (flat/percent) ke platform_xendit_for_user_id
     *
     * $options:
     * - name: string|null
     * - description: string|null
     * - platform_xendit_for_user_id: string|null
     * - platform_flat_amount: int|null (IDR)
     * - platform_percent_amount: float|null (0..100)
     * - currency: string (default 'IDR')
     */
    public function resolveSplitRuleId(Transaction $trx, array $options = []): ?array
    {
        // 1) group per admin (gunakan helper yg sudah ada)
        $groups = $this->groupItemsByAdmin($trx);

        if (empty($groups)) {
            // tidak ada item yg bisa dibagi → tidak perlu split rule
            return null;
        }

        // 2) alokasi diskon/tax/surcharge proporsional → nominal per-admin
        $allocs = $this->allocateAdjustmentsPerGroup($trx, $groups);
        $total  = array_sum(array_map(fn($a) => (float) $a['amount'], $allocs));

        if ($total <= 0) {
            throw new InvalidArgumentException('Total amount per-admin <= 0; unable to create split rule.');
        }
        // 3) siapkan platform fee (opsional)
        $currency        = (string) ($options['currency'] ?? 'IDR');
        $platformForUser = (string) ($options['platform_xendit_for_user_id'] ?? config('xendit.platform_for_user_id'));
        $platFlat        = (int)    ($options['platform_flat_amount'] ?? 0);
        $platPercent     = (float)  ($options['platform_percent_amount'] ?? 0.0);

        $routes = [];

        if ($platformForUser) {
            if ($platFlat > 0) {
                $routes[] = [
                    'flat_amount'            => $platFlat,
                    'currency'               => $currency,
                    'destination_account_id' => $platformForUser,
                    'reference_id'           => "plat-flat-{$trx->id}-" . Str::uuid()->toString(),
                ];
            }
            if ($platPercent > 0) {
                // pastikan aman di rentang 0..100
                $routes[] = [
                    'percent_amount'         => max(0, min(100, $platPercent)),
                    'currency'               => $currency,
                    'destination_account_id' => $platformForUser,
                    'reference_id'           => "plat-percent-{$trx->id}-" . Str::uuid()->toString(),
                ];
            }
        }

        // 4) hitung porsi persen utk masing-masing admin
        //    total persen untuk merchant harus = 100 - platformPercent (kalau ada)
        // $percentBudget = 100.0 - ($platPercent > 0 ? max(0, min(100, $platPercent)) : 0.0);
        $admins        = array_keys($groups);
        // $n             = count($admins);
        // agar rounding rapi, admin terakhir dapat sisa persen
        // $sumPerc = 0.0;
        $routesPlan = [];
        foreach ($admins as $idx => $adminId) {
            $admin = $groups[$adminId]['admin'];
            $dest  = (string) ($admin->xendit_for_user_id ?? '');
            if ($dest === '') {
                throw new InvalidArgumentException("Admin #{$admin->id} missing xendit_for_user_id.");
            }

            $amount = (float) $allocs[$adminId]['amount'];
            // $share  = $amount / $total;
            // $perc   = ($idx === $n - 1)
            //     ? max(0.0, $percentBudget - $sumPerc)
            //     : round($percentBudget * $share, 2);

            // $sumPerc += ($idx === $n - 1) ? 0.0 : $perc;
            // dd($sumPerc);

            // $amount = (float) $allocs[$adminId]['amount'];
            // $share  = $amount / $total;
            // $perc   = ($idx === $n - 1)
            //     ? max(0.0, $percentBudget - $sumPerc)
            //     : round($percentBudget * $share, 2);

            // $sumPerc += ($idx === $n - 1) ? 0.0 : $perc;

            // $admin = $groups[$adminId]['admin'];
            // $dest  = (string) ($admin->xendit_for_user_id ?? '');
            // if ($dest === '') {
            //     throw new InvalidArgumentException("Admin #{$admin->id} does not have a xendit_for_user_id.");
            // }

            $routes[] = [
                // 'percent_amount'         => $perc,
                'flat_amount'            => $amount,
                'currency'               => $currency,
                'destination_account_id' => $dest,
                'reference_id'           => Str::upper("merchant-{$trx->id}-{$admin->id}-" . Str::uuid()->toString()),
            ];

            $routesPlan[] = [
                'admin_id'               => (int) $admin->id,
                'destination_account_id' => $dest,
                'currency'               => $currency,
                'flat_amount'            => $amount, // rupiah
                'reference_id'           => Str::upper("merchant-{$trx->id}-{$admin->id}-" . Str::uuid()->toString()),
                'meta'                   => [
                    'group_subtotal' => $allocs[$adminId]['subtotal'],
                    'disc_part'      => $allocs[$adminId]['discount_part'],
                    'tax_part'       => $allocs[$adminId]['tax_part'],
                    'surcharge_part' => $allocs[$adminId]['surcharge_part'],
                ],
            ];
        }

        $name = preg_replace('/[^a-zA-Z0-9 ]/', '', ('Auto Split: ' . ($trx->number ?? $trx->id)) ?? 'Split Rule');
        $description = preg_replace('/[^a-zA-Z0-9 ]/', '', ('Auto generated for transaction ' . ($trx->number ?? $trx->id)));

        // 5) panggil Xendit: create split rule
        $payload = [
            'name'        => $options['name']        ?? $name,
            'description' => $options['description'] ?? $description,
            'routes'      => $routes,
        ];

        /** @var SplitRuleService $splitSvc */
        // $splitSvc = app(SplitRuleService::class);
        $data = FeeConfigData::from($payload);
        $resp = $this->paymentRequestService->createSplitRule($data);

        // Xendit balikin id split rule di salah satu key ini (bergantung versi API)
        $splitRuleId = $resp['id'] ?? $resp['split_rule_id'] ?? null;
        if (!$splitRuleId) {
            throw new \RuntimeException('Failed to get split_rule_id from Xendit response.');
        }

        return [$splitRuleId, $routesPlan];
    }

    protected function groupItemsByAdmin(Transaction $trx): array
    {
        $trx->loadMissing('transaction_items.pickup_fee.waste_type.admin', 'transaction_items.pickup_schedule.admin');

        $groups = [];
        foreach ($trx->transaction_items as $it) {
            if (!in_array($it->item_type, ['pickup', 'other'], true)) continue;

            $admin = $it->pickup_fee->admin
                ?? $it->pickup_schedule->admin
                ?? null;

            if (!$admin || empty($admin->xendit_for_user_id)) {
                throw new InvalidArgumentException("Admin or xendit_for_user_id is empty for item #{$it->id}");
            }
            $key = (string) $admin->id;
            $groups[$key] ??= ['admin' => $admin, 'items' => collect()];
            $groups[$key]['items']->push($it);
        }

        return $groups;
    }

    protected function allocateAdjustmentsPerGroup(Transaction $trx, array $groups): array
    {
        $baseSubtotal = (float) $trx->transaction_items()
            ->whereIn('item_type', ['pickup', 'other'])
            ->sum('line_total');

        $totalDiscount  = (float) $trx->transaction_items()->where('item_type', 'discount')->sum('line_total');
        $totalTax       = (float) $trx->transaction_items()->where('item_type', 'tax')->sum('line_total');
        $totalSurcharge = (float) $trx->transaction_items()->where('item_type', 'surcharge')->sum('line_total');

        $result = [];
        foreach ($groups as $key => $g) {
            $groupSubtotal = (float) $g['items']->sum('line_total');
            $ratio         = $baseSubtotal > 0 ? ($groupSubtotal / $baseSubtotal) : 0.0;

            $disc = $totalDiscount  * $ratio;
            $tax  = $totalTax       * $ratio;
            $surch = $totalSurcharge * $ratio;

            $amount = max(0, $groupSubtotal - $disc + $tax + $surch);

            $result[$key] = [
                'amount'        => $amount,
                'subtotal'      => $groupSubtotal,
                'discount_part' => $disc,
                'tax_part'      => $tax,
                'surcharge_part' => $surch,
            ];
        }
        return $result;
    }
}
