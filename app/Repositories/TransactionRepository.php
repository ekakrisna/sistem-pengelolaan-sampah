<?php

namespace App\Repositories;

use App\Data\UserData;
use App\Models\PickupFee;
use App\Models\PickupSchedule;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\UserAddress;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionRepository
{
    protected Transaction $transaction;
    protected array $with = [
        'payments',
        'customer',
        'transaction_items.user_address.village.district.city.province',
        'transaction_items.pickup_schedule.waste_type',
        'transaction_items.pickup_fee.waste_type',
    ];

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

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

    public function all(?UserData $user = null)
    {
        return $this->transaction->newQuery()
            ->with($this->with)
            ->get();
    }

    public function getById(int $id, ?UserData $user = null)
    {
        return $this->transaction->newQuery()
            ->with($this->with)
            ->whereKey($id)
            ->firstOrFail();
    }

    public function save(array $data)
    {
        $transaction = $this->transaction->newQuery()->create($data);
        return $transaction->load($this->with);
    }

    public function update(array $data, int $id, ?UserData $user = null)
    {
        $trx = $this->transaction->newQuery()->findOrFail($id);
        $trx->update($data);
        return $trx->load($this->with);
    }

    public function delete(int $id, ?UserData $user = null)
    {
        $trx = $this->transaction->newQuery()->findOrFail($id);
        $trx->delete();
        return $trx;
    }

    public function paginateWithFilters(array $filters = [], int $pageSize = 10, ?UserData $user = null)
    {
        $query = $this->transaction->newQuery()->with($this->with);
        // Filter by village
        if (!empty($filters['village'])) {
            $query->whereHas('transaction_items.pickup_schedule.village', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['village'] . '%');
            });
        }

        // Filter by waste type
        if (!empty($filters['waste_type'])) {
            $query->whereHas('transaction_items.pickup_schedule.waste_type', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['waste_type'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['waste_type'] . '%');
            });
        }

        // Filter by admin
        if (!empty($filters['admin'])) {
            $query->whereHas('transaction_items.pickup_schedule.admin', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['admin'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['admin'] . '%');
            });
        }

        // Filter by trasaction status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
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
    }

    public function getOrCreateDraftCart(int $customerId, array $meta = []): Transaction
    {
        $cart = $this->getDraftCart($customerId);
        return $cart ?: $this->createDraftCart($customerId, $meta);
    }

    public function addItemToCart(int $transactionId, array $itemData, int $currentUserId): TransactionItem
    {
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
    }

    public function updateItemInCart(int $transactionId, int $itemId, array $itemData, int $currentUserId): TransactionItem
    {
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
        $qtyChanged = false;
        if (array_key_exists('qty', $itemData) && !is_null($itemData['qty'])) {
            $newQty = max(1, (int)$itemData['qty']);
            if ($newQty !== (int)$item->qty) $qtyChanged = true;
            $item->qty = $newQty;
        }

        $feeChanged = false;
        if (array_key_exists('pickup_fee_id', $itemData) && !is_null($itemData['pickup_fee_id'])) {
            $fee = PickupFee::findOrFail($itemData['pickup_fee_id']);
            if ((int)$item->pickup_fee_id !== (int)$fee->id) $feeChanged = true;

            $item->pickup_fee_id = $fee->id;
            $item->unit_amount   = (float) $fee->amount;
        }

        if ($item->item_type === 'pickup' && $item->pickup_fee_id) {
            $fee = PickupFee::findOrFail($item->pickup_fee_id);

            // Jika fee diganti, paksa set ulang periode agar konsisten
            $this->applyPeriodFromFee($item, $fee, true);
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

        if ($itemType === 'discount') {
            // Ambil nilai baru yang diminta
            $qtyNew  = array_key_exists('qty', $itemData) ? max(1, (int)$itemData['qty']) : (int) $item->qty;
            $unitNew = array_key_exists('unit_amount', $itemData)
                ? (float) $itemData['unit_amount']
                : (float) $item->unit_amount;

            $incoming = $qtyNew * $unitNew;

            // HARD-FAIL: hitung terhadap subtotal + exclude baris ini (supaya tidak double count)
            $this->assertDiscountNotExceedSubtotal($trx, $incoming, $item->id);

            // set ulang nilai
            $item->qty         = $qtyNew;
            $item->unit_amount = $unitNew;
            $item->line_total  = $unitNew * $qtyNew;
        }

        if ($itemType === 'pickup') {
            if (!$item->pickup_fee_id) {
                throw new \InvalidArgumentException('pickup_fee_id required for pickup item.');
            }
        } else {
            if (array_key_exists('unit_amount', $itemData) && !is_null($itemData['unit_amount'])) {
                $item->unit_amount = (float) $itemData['unit_amount'];
            }
            $item->line_total = $item->unit_amount * $item->qty;
        }

        if ($item->item_type === 'pickup' && $item->pickup_fee_id && ($qtyChanged || $feeChanged)) {
            $fee = $fee ?? PickupFee::findOrFail($item->pickup_fee_id);
            $this->applyPeriodFromFee($item, $fee, true);
        }

        if ($item->item_type === 'pickup' && $item->pickup_fee_id) {
            $fee = $fee ?? PickupFee::findOrFail($item->pickup_fee_id);
            $this->applyPeriodFromFee($item, $fee, false);
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
    }

    public function removeItemFromCart(int $transactionId, int $itemId, int $currentUserId): bool
    {
        // 1) lock
        $trx = $this->lockDraftForUser($transactionId, $currentUserId);

        $this->invalidateSnapshotIfAny($trx);

        // 2) delete
        $deleted = $trx->transaction_items()->where('id', $itemId)->delete();

        // 3) normalize + recalc (jaga konsistensi)
        $this->normalizeAndRecalc($trx);

        return (bool) $deleted;
    }

    public function checkoutCart(int $transactionId, int $currentUserId, array $meta = []): Transaction
    {
        $trx = $this->transaction->newQuery()
            ->where('id', $transactionId)
            ->where('customer_id', $currentUserId)
            ->where('status', 'draft')
            ->lockForUpdate()
            ->firstOrFail();

        $this->normalizeAndRecalc($trx);
        $this->assertOwnershipAndCompatibility($trx);

        if ($trx->transaction_items()->count() === 0) {
            throw new \InvalidArgumentException('Cannot checkout empty cart.');
        }

        $trx->amount_snapshot = $trx->total;
        $trx->items_snapshot  = $trx->transaction_items()
            ->get(['item_type', 'user_address_id', 'pickup_schedule_id', 'pickup_fee_id', 'unit_amount', 'qty', 'line_total'])
            ->toArray();
        $trx->snapshot_at     = now();
        $trx->snapshot_version = ($trx->snapshot_version ?? 0) + 1;

        $userNote = $meta['notes'] ?? null;

        $trx->description = $this->makeCheckoutDescription($trx, $userNote);

        unset($meta['channel_code']);
        if (!empty($meta)) {
            $trx->meta = array_merge((array) ($trx->meta ?? []), $meta);
        }

        $trx->save();

        return $trx->fresh($this->with);
    }

    /** ---------------- Helpers ---------------- */
    protected function recalculateTotals(Transaction $trx): void
    {
        $items = $trx->transaction_items()->get(['item_type', 'line_total']);

        $pickupTotal    = (float) $items->where('item_type', 'pickup')->sum('line_total');
        $otherTotal     = (float) $items->where('item_type', 'other')->sum('line_total');
        $surchargeTotal = (float) $items->where('item_type', 'surcharge')->sum('line_total');
        $taxTotal       = (float) $items->where('item_type', 'tax')->sum('line_total');
        $rawDiscount    = (float) $items->where('item_type', 'discount')->sum('line_total');

        $subtotal       = $pickupTotal + $otherTotal;

        // Soft-cap: diskon tidak boleh melebihi subtotal
        $effectiveDiscount = min($rawDiscount, $subtotal);
        $discountCapped    = $rawDiscount > $effectiveDiscount;

        $total = ($subtotal - $effectiveDiscount) + $surchargeTotal + $taxTotal;

        // (opsional) tandai di meta kalau diskon dicap
        if ($discountCapped) {
            $meta = (array) ($trx->meta ?? []);
            $meta['discount_capped'] = [
                'raw'       => $rawDiscount,
                'effective' => $effectiveDiscount,
                'capped'    => $rawDiscount - $effectiveDiscount,
                'at'        => now()->toIso8601String(),
            ];
            $trx->meta = $meta;
        }

        $trx->update([
            'subtotal'        => $subtotal,
            'discount_amount' => $effectiveDiscount,
            'tax_amount'      => $taxTotal,
            'total'           => $total,
        ]);
    }


    protected function generateNumber(): string
    {
        return 'INV-' . now()->format('Ym') . '-' . Str::upper(Str::uuid()->toString());
    }

    public function lockAndGetForCheckout(int $transactionId, int $currentUserId): Transaction
    {
        $trx = $this->transaction->newQuery()
            ->where('id', $transactionId)
            ->where('customer_id', $currentUserId)
            ->where('status', 'draft')
            ->lockForUpdate()
            ->firstOrFail();

        $this->recalculateTotals($trx);

        return $trx->fresh('transaction_items');
    }

    public function normalizeAndRecalc(Transaction $trx): void
    {
        $items = $trx->transaction_items()->get();

        foreach ($items as $item) {
            if ($item->item_type === 'pickup' && $item->pickup_fee_id) {
                $fee = PickupFee::findOrFail($item->pickup_fee_id);

                // force dari fee
                $item->unit_amount = (float) $fee->amount;
                $item->line_total  = $item->unit_amount * $item->qty;

                // set periode (subscription window) dari fee
                $this->applyPeriodFromFee($item, $fee, true);
                $item->save();
            } else {
                // non-pickup: pastikan konsisten (unit_amount >= 0, qty >= 1)
                $item->qty         = max(1, (int)$item->qty);
                $item->unit_amount = max(0, (float)$item->unit_amount);
                $item->line_total  = $item->unit_amount * $item->qty;
                $item->save();
            }
        }

        // gabungkan duplikat pickup
        $this->mergeDuplicates($trx);

        // hitung ulang total per bucket
        $this->recalculateTotals($trx);

        // Hard guard: total harus > 0 agar valid untuk pembayaran
        $trx->refresh();
        if ((float)$trx->total <= 0) {
            throw new \InvalidArgumentException('Total amount must be greater than zero.');
        }
    }

    public function assertOwnershipAndCompatibility(Transaction $trx): void
    {
        $uid = $trx->customer_id;

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
                    $addr = UserAddress::findOrFail($it->user_address_id);

                    if (!is_null($addr->village_code) && $addr->village_code !== $sch->village_code) {
                        throw new \InvalidArgumentException('Address village does not match schedule village.');
                    }

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
                continue;
            }

            $keeper = $items->first();
            $others = $items->slice(1);

            $totalQty = (int)$items->sum('qty');

            $fee = PickupFee::findOrFail($g->pickup_fee_id);
            $keeper->qty         = max(1, $totalQty);
            $keeper->unit_amount = (float)$fee->amount;
            $keeper->line_total  = $keeper->unit_amount * $keeper->qty;

            $this->applyPeriodFromFee($keeper, $fee, true);

            $descParts = array_filter($items->pluck('description')->all(), fn($v) => filled($v));
            if (!empty($descParts)) {
                $keeper->description = implode(' / ', array_values(array_unique($descParts)));
            }

            $keeper->save();

            TransactionItem::query()
                ->whereIn('id', $others->pluck('id'))
                ->delete();
        }

        $this->recalculateTotals($trx);
    }

    public function lockDraftForUser(int $transactionId, int $currentUserId): Transaction
    {
        $trx = $this->transaction->newQuery()
            ->where('id', $transactionId)
            ->where('customer_id', $currentUserId)
            ->where('status', 'draft')
            ->lockForUpdate()
            ->firstOrFail();

        return $trx;
    }

    public function ensureNumber(Transaction $trx): void
    {
        if (!empty($trx->number)) return;

        $trx->number = $this->generateNumber();
        $trx->save();
    }

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

    protected function insertOrMergeItem(Transaction $trx, array $itemData): TransactionItem
    {
        $itemType = $itemData['item_type'] ?? 'pickup';
        $qtyReq   = max(1, (int)($itemData['qty'] ?? 1));

        if ($itemType === 'pickup') {
            if (empty($itemData['pickup_fee_id'])) {
                throw new \InvalidArgumentException('pickup_fee_id is required for pickup item.');
            }

            $fee = PickupFee::findOrFail($itemData['pickup_fee_id']);

            if (!empty($itemData['pickup_schedule_id'])) {
                PickupSchedule::findOrFail($itemData['pickup_schedule_id']);
            }

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
                    $existing->restore();

                    $existing->qty        = $qtyReq;
                } else {
                    $existing->qty        += $qtyReq;
                }

                $existing->unit_amount = (float) $fee->amount;
                $existing->line_total  = $existing->unit_amount * $existing->qty;

                if (!empty($itemData['description'])) {
                    $existing->description = $itemData['description'];
                }
                if (!empty($itemData['meta'])) {
                    $existing->meta        = $itemData['meta'];
                }

                $this->applyPeriodFromFee($existing, $fee, true);

                $existing->save();
                return $existing->fresh();
            }

            $unitAmount = (float) $fee->amount;
            $item = $trx->transaction_items()->create([
                'item_type'           => 'pickup',
                'user_address_id'     => $itemData['user_address_id'] ?? null,
                'pickup_schedule_id'  => $itemData['pickup_schedule_id'] ?? null,
                'pickup_fee_id'       => $fee->id,
                'description'         => $itemData['description'] ?? null,
                'unit_amount'         => $unitAmount,
                'qty'                 => $qtyReq,
                'line_total'          => $unitAmount * $qtyReq,
                'meta'                => $itemData['meta'] ?? null,
            ]);

            $this->applyPeriodFromFee($item, $fee, true);
            $item->save();

            return $item->fresh();
        }

        if ($itemType === 'discount') {
            if (!isset($itemData['unit_amount'])) {
                throw new \InvalidArgumentException('unit_amount is required for discount item.');
            }
            $qty        = max(1, (int)($itemData['qty'] ?? 1));
            $unitAmount = (float) $itemData['unit_amount'];
            $incoming   = $unitAmount * $qty;

            // HARD-FAIL jika melewati subtotal
            $this->assertDiscountNotExceedSubtotal($trx, $incoming, null);
        }

        if (!isset($itemData['unit_amount'])) {
            throw new \InvalidArgumentException('unit_amount is required for non-pickup item.');
        }

        $mergeKeyDesc = $itemData['description'] ?? null;

        $qty        = $qtyReq;
        $unitAmount = max(0, (float)$itemData['unit_amount']);
        $lineTotal  = $unitAmount * $qty;

        $existing = $trx->transaction_items()
            ->withTrashed()
            ->where('item_type', $itemType)
            ->where('description', $mergeKeyDesc)
            ->where('unit_amount', $unitAmount)
            ->whereNull('pickup_fee_id')        // non-pickup punya pickup_fee_id null
            ->whereNull('pickup_schedule_id')
            ->whereNull('user_address_id')
            ->orderBy('id', 'asc')
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
                $existing->qty = $qty; // dihidupkan lagi sebagai qty baru
            } else {
                $existing->qty += $qty; // tambah qty
            }

            $existing->unit_amount = $unitAmount;
            $existing->line_total  = $existing->unit_amount * $existing->qty;

            if (!empty($itemData['meta'])) $existing->meta = $itemData['meta'];

            $existing->save();
            return $existing->fresh();
        }

        // Tidak ada yang bisa di-merge → buat baris baru
        return $trx->transaction_items()->create([
            'item_type'           => $itemType,
            'user_address_id'     => null,
            'pickup_schedule_id'  => null,
            'pickup_fee_id'       => null,
            'description'         => $mergeKeyDesc,
            'unit_amount'         => $unitAmount,
            'qty'                 => $qty,
            'line_total'          => $lineTotal,
            'meta'                => $itemData['meta'] ?? null,
        ])->fresh();
    }

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

    protected function makeCheckoutDescription(Transaction $trx, ?string $userNote = null): string
    {

        $items = $trx->transaction_items()
            ->with(['pickup_fee.waste_type', 'pickup_schedule.waste_type'])
            ->get();

        $pickupItems = $items->where('item_type', 'pickup');
        $pickupCount = (int) $pickupItems->sum('qty');

        $uniqueAddressCount = $pickupItems
            ->pluck('user_address_id')
            ->filter()
            ->unique()
            ->count();

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

            $key = $wasteTypeId ? "WT-{$wasteTypeName}-{$wasteTypeId}" : 'WT-unknown';

            $wasteTypeCounts[$key] = ($wasteTypeCounts[$key] ?? 0) + (int) $it->qty;
        }

        $wasteSummary = [];
        foreach ($wasteTypeCounts as $key => $qty) {
            $wasteSummary[] = "{$key}-{$qty}x";
        }
        $wastePart = empty($wasteSummary) ? '' : ' (' . implode(', ', $wasteSummary) . ')';

        $totalPart = 'Total: Rp ' . number_format((float) $trx->total, 0, ',', '.');

        $addrPart = $uniqueAddressCount > 0 ? " di {$uniqueAddressCount} alamat" : '';

        $base = "{$pickupCount}x pickup{$wastePart}{$addrPart} • {$totalPart}";

        if ($userNote && filled($userNote)) {
            $base .= ' • Catatan: ' . Str::limit($userNote, 120);
        }

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

    /**
     * Hitung periode start/end berdasarkan fee.
     * - anchor default: now() di timezone app
     * - untuk 'month' pakai addMonthsNoOverflow agar aman di akhir bulan
     */
    protected function computePeriodRange(
        PickupFee $fee,
        ?CarbonInterface $anchor = null,
        int $qty = 1
    ): array {
        $start = ($anchor ?? now())->copy()->startOfSecond();

        $unit   = $fee->interval_unit;             // 'day' | 'week' | 'month' | 'year'
        $count  = max(1, (int)$fee->interval_count);
        $qty    = max(1, (int)$qty);


        // Qty memperpanjang panjang periode
        $totalCount = $count * $qty;

        $end = match ($unit) {
            'day'   => $start->copy()->addDays($totalCount)->subSecond(),
            'week'  => $start->copy()->addWeeks($totalCount)->subSecond(),
            'month' => $start->copy()->addMonthsNoOverflow($totalCount)->subSecond(),
            'year'  => $start->copy()->addYears($totalCount)->subSecond(),
            default => $start->copy()->addMonthsNoOverflow($totalCount)->subSecond(),
        };

        return [$start, $end];
    }

    /**
     * Tentukan anchor waktu untuk item (utamakan info dari schedule kalau ada).
     * Silakan sesuaikan nama kolom tanggal di PickupSchedule jika berbeda.
     */
    protected function resolveAnchorForItem(TransactionItem $item): CarbonInterface
    {
        // Prioritas: schedule date/time kalau ada
        if ($item->pickup_schedule_id && $item->relationLoaded('pickup_schedule') && $item->pickup_schedule) {
            $sch = $item->pickup_schedule;

            // Ganti field berikut sesuai skema kamu:
            // contoh: $sch->run_at, $sch->scheduled_for, $sch->pickup_date, dst.
            if (!empty($sch->run_at)) {
                return Carbon::parse($sch->run_at);
            }
            if (!empty($sch->scheduled_for)) {
                return Carbon::parse($sch->scheduled_for)->startOfDay();
            }
            if (!empty($sch->pickup_date)) {
                return Carbon::parse($sch->pickup_date)->startOfDay();
            }
        }

        return now();
    }

    /**
     * Set current_period_start/end pada item pickup sesuai fee & anchor.
     * - Jika $force = true: selalu set ulang periode (misal saat fee berubah)
     * - Jika $force = false: hanya isi bila null
     */
    protected function applyPeriodFromFee(TransactionItem $item, PickupFee $fee, bool $force = false): void
    {
        $shouldSet = $force
            || is_null($item->current_period_start)
            || is_null($item->current_period_end);

        if (!$shouldSet) return;

        $anchor = $this->resolveAnchorForItem($item);
        [$start, $end] = $this->computePeriodRange(
            $fee,
            $anchor,
            (int)($item->qty ?? 1)
        );

        $item->current_period_start = $start;
        $item->current_period_end   = $end;
    }

    protected function baseSubtotal(Transaction $trx): float
    {
        return (float) $trx->transaction_items()
            ->whereIn('item_type', ['pickup', 'other'])
            ->sum('line_total');
    }

    protected function currentDiscountExcluding(?int $excludeItemId, Transaction $trx): float
    {
        $q = $trx->transaction_items()->where('item_type', 'discount');
        if ($excludeItemId) {
            $q->where('id', '!=', $excludeItemId);
        }
        return (float) $q->sum('line_total');
    }

    protected function assertDiscountNotExceedSubtotal(Transaction $trx, float $incomingDiscount, ?int $excludeItemId = null): void
    {
        $subtotalBase  = $this->baseSubtotal($trx);
        $existingDisc  = $this->currentDiscountExcluding($excludeItemId, $trx);
        $totalDisc     = $existingDisc + $incomingDiscount;

        if ($incomingDiscount <= 0) {
            throw new \InvalidArgumentException('Discount amount must be > 0.');
        }

        if ($totalDisc > $subtotalBase) {
            throw new \InvalidArgumentException('Total discount exceeds subtotal.');
        }
    }
}
