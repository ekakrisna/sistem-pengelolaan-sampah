<?php

namespace App\Services;

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
     */
    public function getById(int $id)
    {
        return $this->transactionRepository->getById($id);
    }

    /**
     * Validate transactionRepository data.
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function save(array $data)
    {
        return $this->transactionRepository->save($data);
    }

    /**
     * Update transactionRepository data
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function update(array $data, int $id)
    {
        DB::beginTransaction();
        try {
            $transactionRepository = $this->transactionRepository->update($data, $id);
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
     * @return String
     */
    public function deleteById(int $id)
    {
        DB::beginTransaction();
        try {
            $transactionRepository = $this->transactionRepository->delete($id);
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
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $filters, int $pageSize = 10)
    {
        return $this->transactionRepository->paginateWithFilters($filters, $pageSize);
    }
}
