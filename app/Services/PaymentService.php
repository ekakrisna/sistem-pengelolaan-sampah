<?php

namespace App\Services;

use App\Models\Payment;
use App\Repositories\PaymentRepository;
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
    public function getAll()
    {
        return $this->paymentRepository->all();
    }

    /**
     * Get paymentRepository by id.
     *
     * @param $id
     * @return String
     */
    public function getById(int $id)
    {
        return $this->paymentRepository->getById($id);
    }

    /**
     * Validate paymentRepository data.
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function save(array $data)
    {
        return $this->paymentRepository->save($data);
    }

    /**
     * Update paymentRepository data
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function update(array $data, int $id)
    {
        DB::beginTransaction();
        try {
            $paymentRepository = $this->paymentRepository->update($data, $id);
            DB::commit();
            return $paymentRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException('Unable to update post data');
        }
    }

    /**
     * Delete paymentRepository by id.
     *
     * @param $id
     * @return String
     */
    public function deleteById(int $id)
    {
        DB::beginTransaction();
        try {
            $paymentRepository = $this->paymentRepository->delete($id);
            DB::commit();
            return $paymentRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException('Unable to delete post data');
        }
    }

    /**
     * @param array $filters
     * @param int $pageSize
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $filters, int $pageSize = 10)
    {
        return $this->paymentRepository->paginateWithFilters($filters, $pageSize);
    }
}
