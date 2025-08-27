<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    /**
     * Admin/Super Admin: allow all.
     */
    public function before(User $user): ?bool
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }
        return null;
    }

    /** List index — boleh untuk user login, data difilter di repository */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Lihat satu transaksi */
    public function view(User $user, Transaction $transaction): bool
    {
        if ($user->isCustomer()) {
            return (int)$transaction->customer_id === (int)$user->id;
        }
        if ($user->isPetugas()) {
            // Opsional: izinkan jika ada pickup pada transaksi ini yang assigned ke petugas
            return $transaction->transaction_items()
                ->whereNotNull('pickup_id')
                ->whereHas('pickup', fn($q) => $q->where('petugas_id', $user->id))
                ->exists();
        }
        return false;
    }

    /** Buat transaksi (cart draft) — customer membuat utk dirinya sendiri */
    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    /** Update transaksi (umum) — batasi ke draft untuk customer */
    public function update(User $user, Transaction $transaction): bool
    {
        return $user->isCustomer()
            && (int)$transaction->customer_id === (int)$user->id
            && $transaction->status === 'draft';
    }

    /** Hapus — hanya draft milik sendiri */
    public function delete(User $user, Transaction $transaction): bool
    {
        return $user->isCustomer()
            && (int)$transaction->customer_id === (int)$user->id
            && $transaction->status === 'draft';
    }

    /** ---- Aksi kustom terkait cart ---- */

    /** Tambah item ke cart */
    public function addItem(User $user, Transaction $transaction): bool
    {
        return $this->update($user, $transaction); // sama aturan dengan update
    }

    /** Hapus item dari cart */
    public function removeItem(User $user, Transaction $transaction): bool
    {
        return $this->update($user, $transaction);
    }

    /** Checkout cart (draft -> pending) — harus milik sendiri & ada item */
    public function checkout(User $user, Transaction $transaction): bool
    {
        if (!($user->isCustomer() && (int)$transaction->customer_id === (int)$user->id)) {
            return false;
        }
        if ($transaction->status !== 'draft') {
            return false;
        }
        return $transaction->transaction_items()->count() > 0;
    }

    /** Cancel pending sebelum bayar */
    public function cancel(User $user, Transaction $transaction): bool
    {
        return $user->isCustomer()
            && (int)$transaction->customer_id === (int)$user->id
            && $transaction->status === 'pending'
            && !$transaction->payments()->where('status', 'succeeded')->exists();
    }

    /** Amend pending (tambah item lalu remake payment) — jika kamu mengizinkan */
    public function amend(User $user, Transaction $transaction): bool
    {
        return $user->isCustomer()
            && (int)$transaction->customer_id === (int)$user->id
            && in_array($transaction->status, ['draft', 'pending'], true)
            && !$transaction->payments()->where('status', 'succeeded')->exists();
    }
}
