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
use App\Repositories\PaymentRepository;
use App\Repositories\TransactionRepository;
use App\Services\Xendits\PaymentRequest\PaymentPayService;
use App\Services\Xendits\PaymentRequest\PaymentRequestService;
use Carbon\CarbonImmutable;
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
            $channelCodeEnum = ChannelCode::from($request['channel_code']); // akan throw jika tidak valid
            $propsArr        = $request['channel_properties'] ?? [];

            // Prioritas: kalau client sudah kirim expires_at, pakai itu; else pakai default per channel
            if (empty($propsArr['expires_at'])) {
                /** @var CarbonImmutable $expiresAt */
                $expiresAt = ChannelExpiry::defaultExpiresAt($channelCodeEnum);
                $propsArr['expires_at'] = $expiresAt->toIso8601String(); // RFC 3339
            } else {
                $expiresAt = CarbonImmutable::parse($propsArr['expires_at']);
            }

            // Simpan di transaksi juga (biar konsisten internal)
            $trx->expires_at = $expiresAt;
            $trx->due_at     = $trx->due_at ?? $expiresAt;
            $trx->save();

            $propsArr['display_name'] = $trx->customer->name;
            // 4) Build DTO strict
            $channelProps = ChannelPropsData::from($propsArr)->toArray();

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
                'channel_code'     => $channelCodeEnum,
                'channel_properties' => $channelProps,
                'description'      => $trx->description,
                'metadata'         => $request['metadata'] ?? null,
                'items'            => null
            ]);

            // 4) Panggil Xendit (DTO → array payload via toPayload())
            $xenditResp = $this->xenditPaymentPay->create($dto);

            // 5) Simpan ke payments + update transaksi ke pending (strict by columns)
            $saved = $this->paymentRepository->createFromXenditStrict(
                $trx,
                $xenditResp,
                $expiresAt
            );

            return [
                'payment'     => $saved['payment'],
                'transaction' => $saved['transaction'],
                'xendit'      => $xenditResp,
            ];
        });
    }
}
