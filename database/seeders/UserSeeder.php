<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        User::truncate();
        Schema::enableForeignKeyConstraints();

        User::factory()->create([
            'name' => 'Super Admin LokaBersih',
            'email' => 'super_admin@lokabersih.com',
            'role' => 'super_admin',
        ]);

        // Buat 1 admin
        User::factory()->create([
            'name' => 'Admin 1 LokaBersih',
            'email' => 'admin1@lokabersih.com',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Admin 2 LokaBersih',
            'email' => 'admin2@lokabersih.com',
            'role' => 'admin',
        ]);

        // Buat 3 petugas
        User::factory()->create([
            'name' => 'Petugas 1 LokaBersih',
            'role' => 'petugas',
            'email' => 'petugas@lokabersih.com',

        ]);

        // Buat 20 customer
        User::factory()->create([
            'name' => 'Customer 1 LokaBersih',
            'role' => 'customer',
            'email' => 'customer@lokabersih.com',
        ]);
    }
}
