<?php

namespace App\Policies;

use App\Enums\StatusPaymentEnum;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
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

    /**
     * List index — boleh untuk user login, data difilter di repository.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Lihat satu payment.
     * - Customer: boleh jika payment miliknya (customer_id).
     * - Petugas: boleh jika payment terkait transaksi yang memiliki pickup assigned ke petugas tsb.
     */
    public function view(User $user, Payment $payment): bool
    {
        if ($user->isCustomer()) {
            return (int) $payment->customer_id === (int) $user->id;
        }

        if ($user->isPetugas()) {
            // Samakan logika dengan TransactionPolicy->view untuk petugas
            $trx = $payment->transaction;
            if (!$trx) return false;

            return $trx->transaction_items()
                ->whereNotNull('pickup_id')
                ->whereHas('pickup', fn($q) => $q->where('petugas_id', $user->id))
                ->exists();
        }

        return false;
    }

    /**
     * (Opsional) Create payment dari sisi customer (biasanya saat mulai proses bayar).
     * Izinkan jika payment ini milik customer tersebut.
     * Note: Banyak sistem tidak membuat Payment manual, melainkan via service;
     * kalau tidak dipakai, boleh dihapus.
     */
    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    /**
     * Update payment — umumnya dibatasi (mis. hanya sistem).
     * Untuk non-admin, default false.
     */
    public function update(User $user, Payment $payment): bool
    {
        // Biasanya tidak diizinkan; jika butuh, batasi ke milik sendiri & belum paid.
        return false;
    }

    /**
     * Hapus payment — umumnya tidak diizinkan untuk non-admin.
     */
    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }

    /**
     * Cancel payment (sebelum dibayar).
     * - Customer: boleh jika payment miliknya dan status masih cancellable.
     * - Petugas: tidak boleh (kecuali kamu ingin izinkan; default false).
     */
    public function cancel(User $user, Payment $payment): bool
    {
        if ($user->isCustomer() && (int) $payment->customer_id === (int) $user->id) {
            return $this->isCancelableStatus($payment);
        }

        // Jika ingin petugas bisa cancel payment yang terkait pickup-nya, ubah ke true + tambahkan pengecekan serupa view()
        return false;
    }

    /**
     * Refund payment — contoh kebijakan bila suatu saat kamu tambahkan endpoint refund.
     * Default: hanya admin via before().
     */
    public function refund(User $user, Payment $payment): bool
    {
        return false;
    }

    /**
     * Retry (buat ulang PR) — contoh kebijakan bila kamu mendukung retry untuk gagal/expired.
     * Customer hanya boleh retry miliknya & status tertentu (FAILED/EXPIRED).
     */
    public function retry(User $user, Payment $payment): bool
    {
        if ($user->isCustomer() && (int) $payment->customer_id === (int) $user->id) {
            return in_array($payment->status, [
                StatusPaymentEnum::FAILED->value,
                StatusPaymentEnum::EXPIRED->value,
                StatusPaymentEnum::CANCELED->value,
            ], true);
        }
        return false;
    }

    /**
     * Helper: status yang masih bisa dicancel oleh customer.
     */
    protected function isCancelableStatus(Payment $payment): bool
    {
        // Belum terbayar dan berada pada status "menunggu aksi"
        if (!empty($payment->paid_at)) {
            return false;
        }

        return in_array($payment->status, [
            StatusPaymentEnum::AWAITING_PAYMENT->value,
            StatusPaymentEnum::INITIATED->value,
            // kalau ada status PENDING di sistemmu, tambahkan di sini
        ], true);
    }
}
