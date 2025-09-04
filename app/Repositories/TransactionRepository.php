<?php

namespace App\Repositories;

use App\Data\UserData;
use App\Models\PickupFee;
use App\Models\PickupSchedule;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionRepository
{
    /**
     * @var Transaction
     */
    protected Transaction $transaction;
    protected array $with = [
        'payments',
        'customer',
        'transaction_items',
    ];

    /**
     * Transaction constructor.
     *
     * @param Transaction $transaction
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Scope by user role:
     * - customer : via payment.customer_id = user->id
     * - petugas  : via pickup.petugas_id   = user->id
     * - admin/super_admin/others : no restriction
     */
    // protected function scopeForUser(?UserData $user, ?Builder $query = null): Builder
    // {
    //     $query ??= $this->transaction->newQuery();

    //     if (!$user) return $query;

    //     $role = $user->role->value ?? $user->role->name ?? (string) $user->role ?? null;
    //     $role = strtolower((string) $role);

    //     return match ($role) {
    //         'customer' => $query->whereHas('payment', fn(Builder $q) => $q->where('customer_id', $user->id)),
    //         'petugas'  => $query->whereHas('pickup',  fn(Builder $q) => $q->where('petugas_id',  $user->id)),
    //         default    => $query,
    //     };
    // }

    /**
     * Get all transaction.
     * @param ?UserData $user
     * @return Transaction $transaction
     */
    public function all(?UserData $user = null)
    {
        return $this->transaction->newQuery()
            ->with($this->with)
            ->get();
    }

    /**
     * Get transaction by id
     *
     * @param $id
     * @param ?UserData $user
     * @return mixed
     */
    public function getById(int $id, ?UserData $user = null)
    {
        return $this->transaction->newQuery()
            ->with($this->with)
            ->whereKey($id)
            ->firstOrFail();
    }

    /**
     * Save Transaction
     *
     * @param $data
     * @return Transaction
     */
    public function save(array $data)
    {
        $transaction = $this->transaction->newQuery()->create($data);
        return $transaction->load($this->with);
    }

    /**
     * Update Transaction
     *
     * @param $data
     * @param $id
     * @param ?UserData $user
     * @return Transaction
     */
    public function update(array $data, int $id, ?UserData $user = null)
    {
        $trx = $this->transaction->newQuery()->findOrFail($id);
        $trx->update($data);
        return $trx->load($this->with);
    }

    /**
     * Delete Transaction
     *
     * @param $data
     * @param ?UserData $user
     * @return Transaction
     */
    public function delete(int $id, ?UserData $user = null)
    {
        $trx = $this->transaction->newQuery()->findOrFail($id);
        $trx->delete();
        return $trx;
    }

    /**
     * @param array $filters
     * @param int $pageSize
     * @param ?UserData $user
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * Pagination + Filters (role-aware)
     *
     * Supported filters:
     * - search           : cari di payment.external_id / invoice_url
     * - status           : payment.status
     * - payment_method   : payment.payment_method
     * - customer         : nama/email/phone customer
     * - petugas          : nama/email/phone petugas
     * - village          : pickup.schedule.village.name
     * - waste_type       : pickup.schedule.wasteType (name/description)
     * - admin            : pickup.schedule.admin (name/email)
     * - start_date/end_date : by transaction.created_at
     * - order_by         : oldest|desc (default desc)
     */
    public function paginateWithFilters(array $filters = [], int $pageSize = 10, ?UserData $user = null)
    {
        $query = $this->transaction->newQuery()->with($this->with);

        // Filter by village
        if (!empty($filters['village'])) {
            $query->whereHas('pickup.schedule.village', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['village'] . '%');
            });
        }

        // Filter by waste type
        if (!empty($filters['waste_type'])) {
            $query->whereHas('pickup.schedule.wasteType', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['waste_type'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['waste_type'] . '%');
            });
        }

        // Filter by admin
        if (!empty($filters['admin'])) {
            $query->whereHas('pickup.schedule.admin', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['admin'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['admin'] . '%');
            });
        }

        // Filter by payment status
        if (!empty($filters['status'])) {
            $query->whereHas('payment', function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            });
        }

        // Filter by payment method
        if (!empty($filters['payment_method'])) {
            $query->whereHas('payment', function ($q) use ($filters) {
                $q->where('payment_method', $filters['payment_method']);
            });
        }

        // Filter by transaction date range
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        // Sorting
        $sort = $filters['order_by'] ?? 'desc';
        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');

        return $query->paginate($pageSize);
    }

    /** ---------------- NEW: Draft Cart Helpers ---------------- */
    public function getDraftCart(int $customerId): ?Transaction
    {
        return $this->transaction->newQuery()
            ->with($this->with)
            ->where('customer_id', $customerId)
            ->where('status', 'draft')
            ->latest('id')
            ->first();
    }

    public function createDraftCart(int $customerId, array $meta = []): Transaction
    {
        // return DB::transaction(function () use ($customerId, $meta) {
        // Lock agar tidak balapan bikin 2 cart
        $existing = $this->getDraftCart($customerId);
        if ($existing) {
            return $existing;
        }

        $trx = $this->transaction->newQuery()->create([
            'customer_id' => $customerId,
            'number'      => $this->generateNumber(),
            'status'      => 'draft',
            'subtotal'    => 0,
            'discount_amount' => 0,
            'tax_amount'  => 0,
            'total'       => 0,
            'currency'    => 'IDR',
            'meta'        => $meta ?: null,
        ]);

        return $trx->load($this->with);
        // });
    }

    public function getOrCreateDraftCart(int $customerId, array $meta = []): Transaction
    {
        $cart = $this->getDraftCart($customerId);
        return $cart ?: $this->createDraftCart($customerId, $meta);
    }

    public function addItemToCart(int $transactionId, array $itemData, int $currentUserId): TransactionItem
    {
        // return DB::transaction(function () use ($transactionId, $itemData, $currentUserId) {
        // 1) lock
        $trx = $this->lockDraftForUser($transactionId, $currentUserId);

        $this->invalidateSnapshotIfAny($trx);

        // 2) address ownership
        $this->assertAddressOwnership($itemData['user_address_id'] ?? null, $currentUserId);

        // 3) insert/merge
        $item = $this->insertOrMergeItem($trx, $itemData);

        // 4) assert lagi: ownership & compatibility (fee-schedule dsb)
        $this->assertOwnershipAndCompatibility($trx);

        // 5) normalize (force harga fee, merge duplikat, recalc total)
        $this->normalizeAndRecalc($trx);

        // throw new \InvalidArgumentException('Invalid item type.');

        // 6) kembalikan keeper utk pickup, atau item itu sendiri
        if (($itemData['item_type'] ?? 'pickup') === 'pickup') {
            return ($this->findPickupKeeper($trx, $itemData) ?? $item)->fresh();
        }

        return $item->fresh();
        // });
    }

    public function updateItemInCart(int $transactionId, int $itemId, array $itemData, int $currentUserId): TransactionItem
    {
        // return DB::transaction(function () use ($transactionId, $itemId, $itemData, $currentUserId) {
        // 1) Lock draft cart milik user
        $trx = $this->lockDraftForUser($transactionId, $currentUserId);

        $this->invalidateSnapshotIfAny($trx);

        // 2) Ambil item
        /** @var TransactionItem $item */
        $item = $trx->transaction_items()->where('id', $itemId)->firstOrFail();

        // 3) Validasi & update address kalau diganti
        if (array_key_exists('user_address_id', $itemData)) {
            $this->assertAddressOwnership($itemData['user_address_id'], $currentUserId);
            $item->user_address_id = $itemData['user_address_id'];
        }

        // 4) Validasi & update schedule kalau diganti
        if (array_key_exists('pickup_schedule_id', $itemData)) {
            if (!is_null($itemData['pickup_schedule_id'])) {
                PickupSchedule::findOrFail($itemData['pickup_schedule_id']);
            }
            $item->pickup_schedule_id = $itemData['pickup_schedule_id'];
        }

        // 5) Validasi & update fee kalau diganti (force harga dari fee)
        if (array_key_exists('pickup_fee_id', $itemData) && !is_null($itemData['pickup_fee_id'])) {
            $fee = PickupFee::findOrFail($itemData['pickup_fee_id']);
            $item->pickup_fee_id = $fee->id;
            $item->unit_amount   = (float) $fee->amount;
        }

        // 6) Update field umum
        if (array_key_exists('qty', $itemData) && !is_null($itemData['qty'])) {
            $item->qty = max(1, (int)$itemData['qty']);
        }
        if (array_key_exists('description', $itemData)) {
            $item->description = $itemData['description'];
        }

        // 7) Tipe item + perhitungan line_total sementara untuk non-pickup
        $itemType = $itemData['item_type'] ?? $item->item_type;
        $item->item_type = $itemType;

        if ($itemType === 'pickup') {
            if (!$item->pickup_fee_id) {
                throw new \InvalidArgumentException('pickup_fee_id required for pickup item.');
            }
            // line_total akan dihitung ulang di normalizeAndRecalc (force harga dari fee)
        } else {
            if (array_key_exists('unit_amount', $itemData) && !is_null($itemData['unit_amount'])) {
                $item->unit_amount = (float) $itemData['unit_amount'];
            }
            $item->line_total = $item->unit_amount * $item->qty;
        }

        $item->save();

        // 8) Double-check: ownership & kompatibilitas fee-schedule
        $this->assertOwnershipAndCompatibility($trx);

        // 9) Normalize: force harga pickup dari fee + mergeDuplicates + recalc total
        $this->normalizeAndRecalc($trx);

        // 10) Jika pickup, kembalikan keeper (bisa berubah akibat merge)
        if ($item->item_type === 'pickup') {
            return ($this->findPickupKeeper($trx, $itemData) ?? $item)->fresh();
        }

        return $item->fresh();
        // });
    }

    public function removeItemFromCart(int $transactionId, int $itemId, int $currentUserId): bool
    {
        // return DB::transaction(function () use ($transactionId, $itemId, $currentUserId) {
        // 1) lock
        $trx = $this->lockDraftForUser($transactionId, $currentUserId);

        $this->invalidateSnapshotIfAny($trx);

        // 2) delete
        $deleted = $trx->transaction_items()->where('id', $itemId)->delete();

        // 3) normalize + recalc (jaga konsistensi)
        $this->normalizeAndRecalc($trx);

        return (bool) $deleted;
        // });
    }

    /** ---------------- Helpers ---------------- */
    protected function recalculateTotals(Transaction $trx): void
    {
        $subtotal = $trx->transaction_items()->sum('line_total');
        $trx->update([
            'subtotal' => $subtotal,
            'total'    => $subtotal - $trx->discount_amount + $trx->tax_amount,
        ]);
    }

    protected function generateNumber(): string
    {
        return 'INV-' . now()->format('Ym') . '-' . Str::upper(Str::uuid()->toString());
    }
    /**
     * Lock & get cart draft milik user (FOR UPDATE) + recalc dulu.
     */
    public function lockAndGetForCheckout(int $transactionId, int $currentUserId): Transaction
    {
        /** @var Transaction $trx */
        $trx = $this->transaction->newQuery()
            ->where('id', $transactionId)
            ->where('customer_id', $currentUserId)
            ->where('status', 'draft')
            ->lockForUpdate()
            ->firstOrFail();

        // hitung ulang sebelum lanjut
        $this->recalculateTotals($trx);

        return $trx->fresh('transaction_items');
    }

    /**
     * Normalisasi cart:
     * - Force harga item pickup = pickup_fees.amount
     * - (opsional) merge duplikat lagi
     * - Recalculate total
     */
    public function normalizeAndRecalc(Transaction $trx): void
    {
        $items = $trx->transaction_items()->get();

        foreach ($items as $item) {
            if ($item->item_type === 'pickup' && $item->pickup_fee_id) {
                $fee = PickupFee::findOrFail($item->pickup_fee_id);
                $item->unit_amount = (float) $fee->amount;
                $item->line_total  = $item->unit_amount * $item->qty;
                $item->save();
            }
        }

        // Jika ingin, kamu bisa panggil ulang mekanisme merge duplikat di sini.
        $this->mergeDuplicates($trx);

        $this->recalculateTotals($trx);
    }

    /**
     * Validasi:
     * - user_address_id milik customer
     * - pickup_fee & pickup_schedule kompatibel (village_code, waste_type_id)
     */
    public function assertOwnershipAndCompatibility(Transaction $trx): void
    {
        $uid = $trx->customer_id;

        // address ownership
        $addrIds = $trx->transaction_items()
            ->whereNotNull('user_address_id')
            ->pluck('user_address_id')
            ->all();

        if (!empty($addrIds)) {
            $owned = UserAddress::whereIn('id', $addrIds)
                ->where('user_id', $uid)
                ->count();

            if ($owned !== count($addrIds)) {
                throw new \InvalidArgumentException('One or more addresses are not owned by the customer.');
            }
        }

        // fee & schedule compatibility
        $items = $trx->transaction_items()->where('item_type', 'pickup')->get();
        foreach ($items as $it) {
            if (!$it->pickup_fee_id) {
                throw new \InvalidArgumentException('pickup_fee_id required for pickup item.');
            }
            $fee = PickupFee::findOrFail($it->pickup_fee_id);

            if ($it->pickup_schedule_id) {
                $sch = PickupSchedule::findOrFail($it->pickup_schedule_id);
                if (
                    $sch->village_code !== $fee->village_code ||
                    (int)$sch->waste_type_id !== (int)$fee->waste_type_id
                ) {
                    throw new \InvalidArgumentException('Pickup fee and schedule are not compatible.');
                }

                if ($it->user_address_id) {
                    /** @var UserAddress $addr */
                    $addr = UserAddress::findOrFail($it->user_address_id);

                    // VARIAN A (disarankan): jika user_addresses punya kolom village_code
                    if (!is_null($addr->village_code) && $addr->village_code !== $sch->village_code) {
                        throw new \InvalidArgumentException('Address village does not match schedule village.');
                    }

                    // VARIAN B (kalau tidak ada kolom village_code di user_addresses):
                    // - uncomment jika kamu punya relasi address->village
                    $addr->loadMissing('village');
                    if (optional($addr->village)->code !== $sch->village_code) {
                        throw new \InvalidArgumentException('Address village does not match schedule village.');
                    }
                }
            }
        }
    }

    public function mergeDuplicates(Transaction $trx): void
    {
        // Cari grup duplikat untuk item pickup dalam transaksi ini
        $dups = TransactionItem::query()
            ->select([
                'user_address_id',
                'pickup_schedule_id',
                'pickup_fee_id',
                DB::raw('COUNT(*) as cnt'),
            ])
            ->where('transaction_id', $trx->id)
            ->where('item_type', 'pickup')
            ->groupBy('user_address_id', 'pickup_schedule_id', 'pickup_fee_id')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($dups as $g) {
            // Ambil semua baris pada grup, kunci untuk update
            $items = TransactionItem::query()
                ->where('transaction_id',    $trx->id)
                ->where('item_type',         'pickup')
                ->where('user_address_id',   $g->user_address_id)
                ->where('pickup_schedule_id', $g->pickup_schedule_id)
                ->where('pickup_fee_id',     $g->pickup_fee_id)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            if ($items->count() < 2) {
                continue; // tidak ada duplikat nyata
            }

            // Keeper = baris pertama (id paling kecil)
            /** @var TransactionItem $keeper */
            $keeper = $items->first();
            $others = $items->slice(1);

            $totalQty = (int)$items->sum('qty');

            // Paksa harga dari fee
            $fee = PickupFee::findOrFail($g->pickup_fee_id);
            $keeper->qty         = max(1, $totalQty);
            $keeper->unit_amount = (float)$fee->amount;
            $keeper->line_total  = $keeper->unit_amount * $keeper->qty;

            // (Opsional) gabungkan deskripsi unik
            $descParts = array_filter($items->pluck('description')->all(), fn($v) => filled($v));
            if (!empty($descParts)) {
                $keeper->description = implode(' / ', array_values(array_unique($descParts)));
            }

            $keeper->save();

            // Hapus baris duplikat lainnya
            TransactionItem::query()
                ->whereIn('id', $others->pluck('id'))
                ->delete();
        }

        // Hitung ulang total transaksi
        $this->recalculateTotals($trx);
    }

    /**
     * Lock transaksi draft milik user (FOR UPDATE).
     */
    public function lockDraftForUser(int $transactionId, int $currentUserId): Transaction
    {
        /** @var Transaction $trx */
        $trx = $this->transaction->newQuery()
            ->where('id', $transactionId)
            ->where('customer_id', $currentUserId)
            ->where('status', 'draft')
            ->lockForUpdate()
            ->firstOrFail();

        return $trx;
    }

    /** 
     * Generate nomor kalau belum ada. 
     */
    public function ensureNumber(Transaction $trx): void
    {
        if (!empty($trx->number)) return;

        $trx->number = $this->generateNumber();
        $trx->save();
    }

    /**
     * Pastikan address (jika ada) dimiliki oleh user.
     */
    protected function assertAddressOwnership(?int $userAddressId, int $currentUserId): void
    {
        if (empty($userAddressId)) return;

        $owned = UserAddress::query()
            ->where('id', $userAddressId)
            ->where('user_id', $currentUserId)
            ->exists();

        if (!$owned) {
            throw new \InvalidArgumentException('Address not found or not owned by the current user.');
        }
    }

    /**
     * Insert atau merge item ke cart.
     * - pickup: enforce harga dari PickupFee, merge berdasar (address,schedule,fee)
     * - non-pickup: langsung create (bisa diubah kalau ingin merge)
     *
     * Return: item keeper (TransactionItem)
     */
    protected function insertOrMergeItem(Transaction $trx, array $itemData): TransactionItem
    {
        $itemType = $itemData['item_type'] ?? 'pickup';
        $qtyReq   = max(1, (int)($itemData['qty'] ?? 1));

        if ($itemType === 'pickup') {
            if (empty($itemData['pickup_fee_id'])) {
                throw new \InvalidArgumentException('pickup_fee_id is required for pickup item.');
            }

            /** @var PickupFee $fee */
            $fee = PickupFee::findOrFail($itemData['pickup_fee_id']);

            // (opsional) validasi schedule ada jika dikirim
            if (!empty($itemData['pickup_schedule_id'])) {
                PickupSchedule::findOrFail($itemData['pickup_schedule_id']);
            }

            // Merge on insert
            $existing = $trx->transaction_items()
                ->withTrashed()
                ->where('item_type', 'pickup')
                ->where('user_address_id',     $itemData['user_address_id'] ?? null)
                ->where('pickup_schedule_id',  $itemData['pickup_schedule_id'] ?? null)
                ->where('pickup_fee_id',       $fee->id)
                ->orderBy('id', 'asc')
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore(); // ⬅️ balikkan dari soft delete
                }

                $existing->qty        += $qtyReq;
                $existing->unit_amount = (float) $fee->amount; // force harga
                $existing->line_total  = $existing->unit_amount * $existing->qty;
                if (!empty($itemData['description'])) {
                    $existing->description = $itemData['description'];
                }
                if (!empty($itemData['meta'])) {
                    $existing->meta        = $itemData['meta'];
                }
                $existing->save();
                return $existing->fresh();
            }

            // buat baris baru
            $unitAmount = (float) $fee->amount;
            return $trx->transaction_items()->create([
                'item_type'           => 'pickup',
                'user_address_id'     => $itemData['user_address_id'] ?? null,
                'pickup_schedule_id'  => $itemData['pickup_schedule_id'] ?? null,
                'pickup_fee_id'       => $fee->id,
                'description'         => $itemData['description'] ?? null,
                'unit_amount'         => $unitAmount,
                'qty'                 => $qtyReq,
                'line_total'          => $unitAmount * $qtyReq,
                'meta'                => $itemData['meta'] ?? null,
            ])->fresh();
        }

        // non-pickup
        if (!isset($itemData['unit_amount'])) {
            throw new \InvalidArgumentException('unit_amount is required for non-pickup item.');
        }

        $qty        = $qtyReq;
        $unitAmount = (float) $itemData['unit_amount'];
        $lineTotal  = $itemData['line_total'] ?? ($unitAmount * $qty);

        return $trx->transaction_items()->create([
            'item_type'           => $itemType,
            'user_address_id'     => $itemData['user_address_id'] ?? null,
            'pickup_schedule_id'  => $itemData['pickup_schedule_id'] ?? null,
            'pickup_fee_id'       => $itemData['pickup_fee_id'] ?? null,
            'description'         => $itemData['description'] ?? null,
            'unit_amount'         => $unitAmount,
            'qty'                 => $qty,
            'line_total'          => $lineTotal,
            'meta'                => $itemData['meta'] ?? null,
        ])->fresh();
    }

    /**
     * (Opsional) cari keeper untuk kombinasi pickup tertentu setelah merge.
     */
    protected function findPickupKeeper(Transaction $trx, array $itemData): ?TransactionItem
    {
        if (!isset($itemData['pickup_fee_id'])) return null;

        return $trx->transaction_items()
            ->where('item_type', 'pickup')
            ->where('user_address_id',     $itemData['user_address_id'] ?? null)
            ->where('pickup_schedule_id',  $itemData['pickup_schedule_id'] ?? null)
            ->where('pickup_fee_id',       $itemData['pickup_fee_id'])
            ->orderBy('id', 'asc')
            ->first();
    }

    public function checkoutCart(int $transactionId, int $currentUserId, array $meta = []): Transaction
    {
        // return DB::transaction(function () use ($transactionId, $currentUserId, $meta) {
        // Lock draft milik user
        /** @var Transaction $trx */
        $trx = $this->transaction->newQuery()
            ->where('id', $transactionId)
            ->where('customer_id', $currentUserId)
            ->where('status', 'draft')
            ->lockForUpdate()
            ->firstOrFail();

        // Normalisasi & validasi
        $this->normalizeAndRecalc($trx);
        $this->assertOwnershipAndCompatibility($trx);

        if ($trx->transaction_items()->count() === 0) {
            throw new \InvalidArgumentException('Cannot checkout empty cart.');
        }

        // snapshot
        $trx->amount_snapshot = $trx->total;
        $trx->items_snapshot  = $trx->transaction_items()
            ->get(['item_type', 'user_address_id', 'pickup_schedule_id', 'pickup_fee_id', 'unit_amount', 'qty', 'line_total'])
            ->toArray();
        $trx->snapshot_at     = now();
        $trx->snapshot_version = ($trx->snapshot_version ?? 0) + 1;

        // HANYA generate description di tahap checkout (STATUS tetap draft)
        // Ambil "notes" (kalau dikirim) untuk disisipkan di deskripsi dan disimpan ke meta
        $userNote = $meta['notes'] ?? null;

        $trx->description = $this->makeCheckoutDescription($trx, $userNote);

        // simpan meta ringan (tanpa channel_code)
        unset($meta['channel_code']);
        if (!empty($meta)) {
            $trx->meta = array_merge((array) ($trx->meta ?? []), $meta);
        }

        $trx->save();

        return $trx->fresh($this->with);
        // });
    }

    /**
     * Buat ringkasan human-readable untuk ditaruh ke transactions.description
     * - Contoh: "2x pickup (Organik) di 1 alamat • Total: Rp 25.000"
     * - Jika ada catatan user, akan ditambahkan di belakang.
     */
    protected function makeCheckoutDescription(Transaction $trx, ?string $userNote = null): string
    {

        $items = $trx->transaction_items()
            ->with(['pickup_fee.waste_type', 'pickup_schedule.waste_type'])
            ->get();

        // Hitung hanya item pickup
        $pickupItems = $items->where('item_type', 'pickup');
        $pickupCount = (int) $pickupItems->sum('qty');

        // Hitung jumlah alamat unik
        $uniqueAddressCount = $pickupItems
            ->pluck('user_address_id')
            ->filter()
            ->unique()
            ->count();

        // Hitung kategori / tipe sampah (berdasarkan pickup_fee_id / schedule)
        // kita pakai waste_type_id dari fee kalau ada, fallback ke schedule
        $wasteTypeCounts = [];
        foreach ($pickupItems as $it) {
            $wasteTypeId = $it->pickup_fee_id && $it->relationLoaded('pickup_fee') && $it->pickup_fee
                ? $it->pickup_fee->waste_type_id
                : ($it->pickup_schedule_id && $it->relationLoaded('pickup_schedule') && $it->pickup_schedule
                    ? $it->pickup_schedule->waste_type_id
                    : null);

            $wasteTypeName = $it->pickup_fee_id && $it->relationLoaded('pickup_fee') && $it->pickup_fee
                ? $it->pickup_fee->waste_type->name
                : ($it->pickup_schedule_id && $it->relationLoaded('pickup_schedule') && $it->pickup_schedule
                    ? $it->pickup_schedule->waste_type->name
                    : '');

            $key = $wasteTypeId ? "WT{$wasteTypeName}#{$wasteTypeId}" : 'WT#unknown';

            $wasteTypeCounts[$key] = ($wasteTypeCounts[$key] ?? 0) + (int) $it->qty;
        }

        // Bentuk potongan text kategori (tanpa nama waste type – jika butuh nama, eager load relasinya di atas)
        // Misal: "WT#3:2x, WT#5:1x"
        $wasteSummary = [];
        foreach ($wasteTypeCounts as $key => $qty) {
            $wasteSummary[] = "{$key}:{$qty}x";
        }
        $wastePart = empty($wasteSummary) ? '' : ' (' . implode(', ', $wasteSummary) . ')';

        // Ringkas total rupiah
        $totalPart = 'Total: Rp ' . number_format((float) $trx->total, 0, ',', '.');

        // Alamat part
        $addrPart = $uniqueAddressCount > 0 ? " di {$uniqueAddressCount} alamat" : '';

        // Build base sentence
        $base = "{$pickupCount}x pickup{$wastePart}{$addrPart} • {$totalPart}";

        // Sisipkan note user jika ada
        if ($userNote && filled($userNote)) {
            $base .= ' • Catatan: ' . Str::limit($userNote, 120);
        }

        // Batas maksimum panjang (opsional)
        return Str::limit($base, 255);
    }

    protected function invalidateSnapshotIfAny(Transaction $trx): void
    {
        if (!is_null($trx->amount_snapshot) || !is_null($trx->items_snapshot)) {
            $trx->amount_snapshot  = null;
            $trx->items_snapshot   = null;
            $trx->snapshot_at      = null;
            $trx->snapshot_version = ($trx->snapshot_version ?? 0) + 1;
            $trx->save();
        }
    }
}
