<?php

namespace App\Services;

use App\Models\Pickup;
use App\Models\PickupFee;
use Illuminate\Support\Facades\Log;

class PricingService
{
    /**
     * Build draft multi-item dari daftar pickup_fee_id.
     * feeItems: [
     *   { "pickup_fee_id": 10, "qty": 1, "description": "Organik bulanan" },
     *   { "pickup_fee_id": 15, "qty": 2 }
     * ]
     * @return array{items: array<int, array>, grand_total: string}
     */
    public function buildDraftFromFeeIds(array $feeItems): array
    {
        if (empty($feeItems) || !is_array($feeItems)) {
            throw new \InvalidArgumentException('fee_items must be a non-empty array');
        }

        $items = [];
        $grand = '0.00';

        foreach ($feeItems as $idx => $row) {
            $feeId = $row['pickup_fee_id'] ?? null;
            $qty   = (int)($row['qty'] ?? 1);
            $desc  = $row['description'] ?? null;

            if (!$feeId) {
                throw new \InvalidArgumentException("fee_items[$idx] requires pickup_fee_id");
            }
            if ($qty < 1) {
                throw new \InvalidArgumentException("fee_items[$idx].qty must be >= 1");
            }

            $fee = PickupFee::with('wasteType')->findOrFail($feeId);

            $unit = (string)$fee->amount;
            $line = bcmul($unit, (string)$qty, 2);
            $grand = bcadd($grand, $line, 2);

            $items[] = [
                'pickup_fee_id' => $fee->id,
                'description'   => $desc ?: $fee->description ?? $fee->wasteType->name,
                'unit_amount'   => $unit,
                'qty'           => $qty,
                'meta'          => [
                    'village_code'    => $fee->village_code,
                    'waste_type_id'   => $fee->waste_type_id,
                    'waste_type_name' => $fee->wasteType->name ?? null,
                    'admin_id'        => $fee->admin_id,
                    'source'          => 'pickup_fees',
                ],
            ];
        }

        return ['items' => $items, 'grand_total' => $grand];
    }

    /**
     * Build draft items dari context pickup.
     * Return: ['items' => [...], 'grand_total' => '12345.00']
     */
    public function buildDraftForPickup(int $pickupId, int $qty = 1): array
    {
        /** @var Pickup $pickup */
        $pickup = Pickup::with(['schedule.wasteType', 'schedule.village'])->findOrFail($pickupId);
        Log::info('[PricingService] Build draft for pickup', [
            'pickup' => $pickup->toArray(),
        ]);
        $villageCode   = $pickup->schedule->code_village_pickup;
        $wasteTypeId   = $pickup->schedule->waste_type_id;
        $adminId       = $pickup->schedule->admin_id ?? $pickup->admin_id ?? null;

        $fee = PickupFee::with('wasteType')
            ->where('village_code', $villageCode)
            ->where('waste_type_id', $wasteTypeId)
            ->when($adminId, fn($q) => $q->where('admin_id', $adminId))
            ->firstOrFail();

        Log::info('[PricingService] Found fee', [
            'fee' => $fee->toArray(),
        ]);

        $items = [
            [
                'pickup_fee_id' => $fee->id,
                'description'   => 'Langganan bulanan - ' . ($fee->wasteType->name ?? 'Unknown'),
                'unit_amount'   => (string) $fee->amount,  // string for decimal safety
                'qty'           => (int) $qty,
                'meta'          => [
                    'village_code'    => $fee->village_code,
                    'waste_type_id'   => $fee->waste_type_id,
                    'waste_type_name' => $fee->wasteType->name ?? null,
                    'admin_id'        => $fee->admin_id,
                    'source'          => 'pickup_fees',
                ],
            ],
        ];

        // ✅ contoh biaya admin (opsional)
        // $items[] = [
        //     'pickup_fee_id' => null,
        //     'description'   => 'Biaya admin',
        //     'unit_amount'   => '2000.00',
        //     'qty'           => 1,
        //     'meta'          => ['source' => 'admin_fee'],
        // ];

        $grand = '0.00';
        foreach ($items as $row) {
            $line = bcmul($row['unit_amount'], (string)$row['qty'], 2);
            $grand = bcadd($grand, $line, 2);
        }

        return ['items' => $items, 'grand_total' => $grand];
    }
}
