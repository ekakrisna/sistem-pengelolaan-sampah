<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pickup;
use Illuminate\Support\Facades\Schema;

class PickupSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Pickup::truncate();
        Schema::enableForeignKeyConstraints();

        Pickup::factory()->count(50)->create();
    }
}
