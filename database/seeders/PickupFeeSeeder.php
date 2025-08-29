<?php

namespace Database\Seeders;

use App\Models\PickupFee;
use App\Models\PickupSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PickupFeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        PickupFee::truncate();
        Schema::enableForeignKeyConstraints();

        PickupFee::factory()->create([
            'village_code' => '1101012001',
            'waste_type_id' => 1,
            'amount' => 10000,
        ]);

        PickupFee::factory()->count(50)->create();
    }
}
