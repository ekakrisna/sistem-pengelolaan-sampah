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
}
