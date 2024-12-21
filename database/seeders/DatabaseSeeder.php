<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            UserRoleSeeder::class,
            // ContactSeeder::class,
            // KloterSeeder::class,
            // KloterNumberSeeder::class,
            // JapoanSeeder::class
        ]);
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '081234567890',
            'address' => 'Jl. Jend. Sudirman No. 1, Jakarta Pusat',
            'user_roles_id' => rand(1, 3)
        ]);
    }
}
