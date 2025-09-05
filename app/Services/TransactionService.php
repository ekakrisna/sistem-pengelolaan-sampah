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
    protected $transactionRepository;

    public function __construct(TransactionRepository $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function getAll()
    {
        return $this->transactionRepository->all();
    }

    public function getById(int $id, ?UserData $user = null)
    {
        return $this->transactionRepository->getById($id, $user);
    }

    public function save(array $data, ?UserData $user = null)
    {
        DB::beginTransaction();
        try {
            $transactionRepository = $this->transactionRepository->save($data, $user);
            DB::commit();
            return $transactionRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw $e;
        }
    }

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
            throw $e;
        }
    }

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
            throw $e;
        }
    }
    public function paginate(array $filters, int $pageSize = 10, ?UserData $user = null)
    {
        return $this->transactionRepository->paginateWithFilters($filters, $pageSize, $user);
    }

    public function getDraftCart(int $customerId)
    {
        return $this->transactionRepository->getDraftCart($customerId);
    }

    public function createDraftCart(int $customerId, array $meta = [])
    {
        DB::beginTransaction();
        try {
            $item = $this->transactionRepository->createDraftCart($customerId, $meta);
            DB::commit();
            return $item;
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    public function getOrCreateDraftCart(int $customerId, array $meta = [])
    {
        return $this->transactionRepository->getOrCreateDraftCart($customerId, $meta);
    }

    public function addItemToCart(int $transactionId, array $itemData, int $currentUserId)
    {
        DB::beginTransaction();
        try {
            $item = $this->transactionRepository->addItemToCart($transactionId, $itemData, $currentUserId);
            DB::commit();
            return $item;
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    public function updateItemInCart(int $transactionId, int $itemId, array $itemData, int $currentUserId)
    {
        DB::beginTransaction();
        try {
            $item = $this->transactionRepository->updateItemInCart($transactionId, $itemId, $itemData, $currentUserId);
            DB::commit();
            return $item;
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    public function removeItemFromCart(int $transactionId, int $itemId, int $currentUserId)
    {
        DB::beginTransaction();
        try {
            $deleted = $this->transactionRepository->removeItemFromCart($transactionId, $itemId, $currentUserId);
            DB::commit();
            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    public function checkoutCart(int $transactionId, int $currentUserId, array $meta = [])
    {
        DB::beginTransaction();
        try {
            $item = $this->transactionRepository->checkoutCart($transactionId, $currentUserId, $meta);
            DB::commit();
            return $item;
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            throw $e;
        }
    }
}
