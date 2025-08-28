<?php

namespace Database\Seeders;

use App\Models\PickupSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PickupScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        PickupSchedule::truncate();
        Schema::enableForeignKeyConstraints();

        $pickupSchedule = PickupSchedule::factory([
            'waste_type_id' => 1,
            'village_code' => '1101012001',
        ])->create();

        PickupSchedule::factory()->count(30)->create();
    }
}
