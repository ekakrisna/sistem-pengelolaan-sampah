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
}
