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
            'name' => 'Admin LokaBersih',
            'email' => 'admin@lokabersih.com',
            'role' => 'admin',
        ]);

        // Buat 3 petugas
        User::factory(3)->create([
            'role' => 'petugas',
        ]);

        // Buat 20 customer
        User::factory(20)->create([
            'role' => 'customer',
        ]);
    }
}
