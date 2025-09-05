<?php

namespace App\Services;

use App\Data\ChannelExpiry;
use App\Data\UserData;
use App\Data\Xendit\Common\ChannelPropsData;
use App\Data\Xendit\Common\Customer\CustomerData;
use App\Data\Xendit\Common\Customer\CustomerIndividualDetailData;
use App\Data\Xendit\PaymentRequest\PaymentsApiPayData;
use App\Enums\Xendit\Common\CaptureMethod;
use App\Enums\Xendit\Common\ChannelCode;
use App\Enums\Xendit\Common\Country;
use App\Enums\Xendit\Common\Currency;
use App\Enums\Xendit\Common\CustomerType;
use App\Models\Payment;
use App\Models\Transaction;
use App\Repositories\PaymentRepository;
use App\Repositories\TransactionRepository;
use App\Services\Xendits\PaymentRequest\PaymentPayService;
use App\Services\Xendits\PaymentRequest\PaymentRequestService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    /**
     * @var PaymentRepository $paymentRepository
     */
    protected PaymentRepository $paymentRepository;
    protected TransactionRepository $transactionRepository;
    protected PaymentPayService $xenditPaymentPay;
    protected PaymentRequestService $paymentRequestService;
    /**
     * DummyClass constructor.
     *
     * @param PaymentRepository $paymentRepository
     */
    public function __construct(
        PaymentRepository $paymentRepository,
        TransactionRepository $transactionRepository,
        PaymentPayService $xenditPaymentPay,
        PaymentRequestService $paymentRequestService
    ) {
        $this->paymentRepository    = $paymentRepository;
        $this->transactionRepository = $transactionRepository;
        $this->xenditPaymentPay     = $xenditPaymentPay;
        $this->paymentRequestService = $paymentRequestService;
    }

    /**
     * Get all paymentRepository.
     *
     * @return String
     */
    public function getAll(?UserData $user = null)
    {
        return $this->paymentRepository->all($user, $user);
    }

    /**
     * Get paymentRepository by id.
     *
     * @param $id
     * @return String
     */
    public function getById(int $id, ?UserData $user = null)
    {
        return $this->paymentRepository->getById($id, $user);
    }

    /**
     * Validate paymentRepository data.
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @param ?UserData $user
     * @return Payment
     */
    public function save(array $data, ?UserData $user = null)
    {
        return $this->paymentRepository->save($data, $user);
    }

    /**
     * Update paymentRepository data
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
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

    /**
     * Delete paymentRepository by id.
     *
     * @param $id
     * @return String
     */
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

    /**
     * @param array $filters
     * @param int $pageSize
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $filters, int $pageSize = 10, ?UserData $user = null)
    {
        return $this->paymentRepository->paginateWithFilters($filters, $pageSize, $user);
    }

    /**
     * Buat PaymentRequest ke Xendit menggunakan DTO PaymentsApiPayData (strict).
     *
     * @param array{
     *   channel_code: string,                 // contoh: "ID_QRIS", "ID_DANA", "ID_BRI_VA", ...
     *   channel_properties?: array,           // sesuai kebutuhan channel (mobile_number/qr/va/etc)
     *   metadata?: array
     * } $request
     *
     * @return array{payment: \App\Models\Payment, transaction: \App\Models\Transaction, xendit: array}
     */
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
            $forUserId   = $this->resolveForUserId($trx, $request);
            $splitRuleId = $this->resolveSplitRuleId($trx, $request);

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
                forUserId: $forUserId,
                splitRuleId: $splitRuleId
            );

            // 5) Simpan ke payments + update transaksi ke pending (strict by columns)
            $saved = $this->paymentRepository->createFromXenditStrict(
                $trx,
                $xenditResp,
                $expiresAt,
                $idempKey,
                $forUserId
            );

            return [
                'payment'     => $saved['payment'],
                'transaction' => $saved['transaction'],
                'xendit'      => $xenditResp,
            ];
        });
    }

    protected function makeIdempotencyKey(
        Transaction $trx,
        ChannelCode $channel,
        CarbonInterface $expiresAt
    ): string {
        return implode(':', [
            'pr',
            $trx->number ?: ('trx' . $trx->id),
            $channel->value,
            (string) (int) round($trx->total * 100),
            (string) $expiresAt->getTimestamp(),
        ]);
    }

    protected function resolveForUserId(Transaction $trx, array $request): ?string
    {
        // 1) Hard override dari client (jika kamu izinkan)
        if (!empty($request['for_user_id'])) {
            return (string) $request['for_user_id'];
        }

        // 2) Dari metadata transaksi (mis: ditetapkan saat cart/checkout)
        $metaFor = data_get($trx->meta, 'xendit_for_user_id');
        if (!empty($metaFor)) {
            return (string) $metaFor;
        }

        // 3) Dari entitas internal (contoh: admin yang mengelola village/waste_type punya subaccount)
        //    Sesuaikan dengan relasi & kolom yang kamu punya.
        if (method_exists($trx, 'admin') && !empty($trx->admin?->xendit_account_id)) {
            return (string) $trx->admin->xendit_account_id;
        }

        // 4) Default: NULL → pakai main account
        return null;
    }

    protected function resolveSplitRuleId(Transaction $trx, array $request): ?string
    {
        // Opsional: kalau kamu pakai split
        if (!empty($request['split_rule_id'])) {
            return (string) $request['split_rule_id'];
        }
        $metaSplit = data_get($trx->meta, 'xendit_split_rule_id');
        return $metaSplit ? (string) $metaSplit : null;
    }
}
