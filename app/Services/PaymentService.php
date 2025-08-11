<?php

namespace App\Services;

use App\Data\UserData;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    /**
     * @var PaymentRepository $paymentRepository
     */
    protected $paymentRepository;

    /**
     * DummyClass constructor.
     *
     * @param PaymentRepository $paymentRepository
     */
    public function __construct(PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
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
            throw new InvalidArgumentException($e->getMessage());
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
            throw new InvalidArgumentException($e->getMessage());
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
     * Simpan Payment lokal dari hasil Payment Request Xendit
     *
     * @param  array $pr   hasil dari PaymentRequestManager->create()
     * @param  array $ctx  context tambahan: customer_id?, channel_category?, channel_code?, currency?, country?, idempotency_key?, dll
     * @return Payment
     */
    public function storeFromXendit(array $pr, array $ctx = [], ?UserData $user = null)
    {
        // 1) Siapkan payload sesuai kolom fillable di model Payment
        $payload = $this->buildPaymentPayloadFromXendit($pr, $ctx);

        // 2) Simpan lewat repository/service save() (bukan langsung model->save)
        $payment = $this->save($payload, $user);

        return $payment;
    }

    /**
     * Susun payload Payment dari response PR Xendit + context
     * Output harus hanya berisi kolom yang ada di $fillable model Payment:
     * - customer_id, amount, status, payment_method, external_id, invoice_url, xendit_data, paid_at
     */
    protected function buildPaymentPayloadFromXendit(array $pr, array $ctx = []): array
    {
        $xStatus = (string) data_get($pr, 'status');   // PENDING|REQUIRES_ACTION|SUCCEEDED|FAILED|EXPIRED|CANCELED
        $status  = $this->mapStatus($xStatus);         // pending|paid|failed

        $paymentMethod = $this->composePaymentMethod(
            channelCategory: (string) ($ctx['channel_category'] ?? data_get($pr, 'payment_method.type')),
            channelCode: (string) ($ctx['channel_code']     ?? $this->extractChannelCode($pr))
        );

        return [
            'customer_id'   => $ctx['customer_id'] ?? null,
            'amount'        => $this->toDecimal(data_get($pr, 'amount', 0)),
            'status'        => $status,
            'payment_method' => $paymentMethod,                                  // "EWALLET:SHOPEEPAY", "QRIS", "VIRTUAL_ACCOUNT:BNI"
            'external_id'   => (string) data_get($pr, 'reference_id'),          // isi ref_id merchant ke kolom external_id
            'invoice_url'   => $this->extractInvoiceUrl($pr),                   // bisa null
            'xendit_data'   => $pr,                                             // cast json di model
            'paid_at'       => $status === 'paid' ? Carbon::now() : null,
        ];
    }

    /** Map status Xendit → status lokal di modelmu */
    protected function mapStatus(string $xStatus): string
    {
        $xStatus = strtoupper($xStatus);
        return match ($xStatus) {
            'SUCCEEDED' => 'paid',
            'PENDING', 'REQUIRES_ACTION' => 'pending',
            default => 'failed', // FAILED, EXPIRED, CANCELED dll
        };
    }

    /** Gabungkan kategori + kode channel jadi satu string untuk kolom payment_method */
    protected function composePaymentMethod(?string $channelCategory, ?string $channelCode): string
    {
        $cat  = strtoupper((string) $channelCategory);
        $code = strtoupper((string) $channelCode);

        if ($cat === '' && $code === '') return '';
        if ($cat !== '' && $code === '') return $cat;
        if ($cat === '' && $code !== '') return $code;
        return $cat . ':' . $code; // contoh: EWALLET:SHOPEEPAY, VIRTUAL_ACCOUNT:BNI, QRIS
    }

    /** Ambil channel_code dari payload PR */
    protected function extractChannelCode(array $pr): ?string
    {
        return data_get($pr, 'payment_method.ewallet.channel_code')
            ?? data_get($pr, 'payment_method.virtual_account.channel_code')
            ?? data_get($pr, 'payment_method.qr_code.channel_code');
    }

    /**
     * Ambil URL yang bisa kamu pakai sebagai invoice_url (opsional)
     * - E-Wallet redirect biasanya ada actions[].url
     * - QRIS kadang menyediakan image URL/string; bisa disimpan di xendit_data saja kalau tidak ada URL
     */
    protected function extractInvoiceUrl(array $pr): ?string
    {
        // PR kadang punya actions array dengan object {action: "REDIRECT", url: "..."}
        $actions = data_get($pr, 'actions', []);
        if (is_array($actions)) {
            foreach ($actions as $a) {
                $url = data_get($a, 'url');
                if ($url) return $url;
            }
        }

        // Beberapa channel mungkin taruh di payment_method.*.channel_properties.* (jarang)
        $fallback = data_get($pr, 'payment_method.ewallet.channel_properties.mobile_web_checkout_url')
            ?? data_get($pr, 'payment_method.ewallet.channel_properties.desktop_web_checkout_url')
            ?? null;

        return $fallback;
    }

    /** Normalisasi angka ke format decimal string 2 digit */
    protected function toDecimal(mixed $val): string
    {
        $num = is_numeric($val) ? (float) $val : 0.0;
        return number_format($num, 2, '.', ''); // "15000.00"
    }
}
