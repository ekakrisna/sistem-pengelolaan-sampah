<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Package::truncate();
        Schema::enableForeignKeyConstraints();

        Package::create(['name' => 'Day Package', 'type' => 'day', 'price' => 10.0]);
        Package::create(['name' => 'Week Package', 'type' => 'week', 'price' => 60.0]);
        Package::create(['name' => 'Month Package', 'type' => 'month', 'price' => 200.0]);
        Package::create(['name' => '3 Month Package', 'type' => '3_month', 'price' => 550.0]);
        Package::create(['name' => '6 Month Package', 'type' => '6_month', 'price' => 1000.0]);
        Package::create(['name' => 'Year Package', 'type' => 'year', 'price' => 1800.0]);
    }
}
