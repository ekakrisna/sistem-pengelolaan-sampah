<?php

namespace Database\Seeders;

use App\Models\PickupFee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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

        PickupFee::factory()->count(50)->create();
    }
}
