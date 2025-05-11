<?php

namespace Database\Seeders;

use App\Models\PickupSchedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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

        PickupSchedule::factory()->count(30)->create();
    }
}
