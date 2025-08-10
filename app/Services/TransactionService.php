<?php

namespace App\Services;

use App\Data\UserData;
use App\Models\Payment;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService
{
    /**
     * @var TransactionRepository $transactionRepository
     */
    protected $transactionRepository;

    /**
     * DummyClass constructor.
     *
     * @param TransactionRepository $transactionRepository
     */
    public function __construct(TransactionRepository $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Get all transactionRepository.
     *
     * @return String
     */
    public function getAll()
    {
        return $this->transactionRepository->all();
    }

    /**
     * Get transactionRepository by id.
     *
     * @param $id
     * @return String
     * @return String
     */
    public function getById(int $id, ?UserData $user = null)
    {
        return $this->transactionRepository->getById($id, $user);
    }

    /**
     * Validate transactionRepository data.
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @param ?UserData $user
     * @return String
     */
    public function save(array $data, ?UserData $user = null)
    {
        return $this->transactionRepository->save($data, $user);
    }

    /**
     * Update transactionRepository data
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @param $id
     * @param ?UserData $user
     * @return String
     */
    public function update(array $data, int $id, ?UserData $user = null)
    {
        DB::beginTransaction();
        try {
            $transactionRepository = $this->transactionRepository->update($data, $id, $user);
            DB::commit();
            return $transactionRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException($e->getMessage());
        }
    }

    /**
     * Delete transactionRepository by id.
     *
     * @param $id
     * @param ?UserData $user
     * @return String
     */
    public function deleteById(int $id, ?UserData $user = null)
    {
        DB::beginTransaction();
        try {
            $transactionRepository = $this->transactionRepository->delete($id, $user);
            DB::commit();
            return $transactionRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException($e->getMessage());
        }
    }

    /**
     * @param array $filters
     * @param int $pageSize
     * @param ?UserData $user
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $filters, int $pageSize = 10, ?UserData $user = null)
    {
        return $this->transactionRepository->paginateWithFilters($filters, $pageSize, $user);
    }

    /**
     * @param  Payment $payment
     * @param  array   $items      // [{pickup_fee_id?, description, unit_amount, qty, meta?}, ...]
     * @param  array   $extra      // {pickup_id?, description?}
     * 
     * @return Transaction
     */
    public function createWithItems(
        Payment $payment,
        array $items,
        array $extra = [],
        ?UserData $user = null
    ) {
        // 1) Normalisasi & hitung total
        [$normalized, $total] = $this->normalizeItemsAndTotal($items);

        // 2) (opsional tapi disarankan) validasi kesesuaian total vs payment->amount
        $this->assertTotalMatchesPayment($total, $payment->amount);

        // 3) Simpan transaction via repository
        $txData = [
            'payment_id'  => $payment->id,
            'pickup_id'   => $extra['pickup_id']   ?? null,
            'total'       => $this->d2($total),
            'description' => $extra['description'] ?? null,
        ];

        return DB::transaction(function () use ($txData, $normalized, $user) {
            /** @var \App\Models\Transaction $tx */
            $tx = $this->save($txData, $user);

            // 4) Bulk insert items via repository (lebih cepat)
            $this->transactionRepository->insertItems($tx->id, $normalized);

            return $tx;
        });
    }

    /** Normalisasi items + total (qty>0 saja, set 2 desimal sebagai string) */
    protected function normalizeItemsAndTotal(array $items): array
    {
        $total = 0.0;
        $rows  = [];

        foreach ($items as $i) {
            $qty  = (int)    ($i['qty'] ?? 0);
            $unit = (float)  ($i['unit_amount'] ?? 0);

            if ($qty <= 0) {
                continue;
            }

            $line = round($qty * $unit, 2);

            $rows[] = [
                'pickup_fee_id' => $i['pickup_fee_id'] ?? null,
                'description'   => $i['description']   ?? null,
                'unit_amount'   => $this->d2($unit),
                'qty'           => $qty,
                'line_total'    => $this->d2($line),
                'meta'          => $i['meta'] ?? null,
            ];

            $total = round($total + $line, 2);
        }

        return [$rows, $total];
    }

    /** Bandingkan total vs payment amount (pakai bccomp jika ada) */
    protected function assertTotalMatchesPayment(float $calcTotal, $paymentAmount): void
    {
        // $paymentAmount kemungkinan string "80000.00" (cast decimal:2)
        $payment = (float) $paymentAmount;
        if (function_exists('bccomp')) {
            if (bccomp((string)$calcTotal, (string)$payment, 2) !== 0) {
                throw new InvalidArgumentException("Items total ({$calcTotal}) doesn't match payment amount ({$payment}).");
            }
            return;
        }

        if (abs($calcTotal - $payment) > 0.00001) {
            throw new InvalidArgumentException("Items total ({$calcTotal}) doesn't match payment amount ({$payment}).");
        }
    }

    /** Format 2 desimal sebagai string */
    protected function d2(float $num): string
    {
        return number_format($num, 2, '.', '');
    }
}
