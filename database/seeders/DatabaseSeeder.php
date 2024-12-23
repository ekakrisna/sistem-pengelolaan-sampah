<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PackageSeeder::class,
            UserRoleSeeder::class,
            PaymentMethodSeeder::class,
            WasteCategorySeeder::class,
            UserSeeder::class,
            (app()->environment('production') ? IndonesiaLocationsSeeder::class : null),
            LocationSeeder::class
        ]);
    }
}
