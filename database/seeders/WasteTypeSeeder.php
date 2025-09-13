<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WasteType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class WasteTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        WasteType::truncate();
        Schema::enableForeignKeyConstraints();

        $admin = User::where('role', 'admin')->inRandomOrder()->first();
        $types = [
            ['Organik', 'Sampah mudah terurai seperti sisa makanan dan daun.'],
            ['Anorganik', 'Sampah sulit terurai seperti plastik, botol, dan kaleng.'],
            ['Elektronik', 'Barang elektronik rusak seperti HP, TV, kabel.'],
            ['B3', 'Bahan Berbahaya & Beracun seperti baterai, oli.'],
        ];

        WasteType::factory()->create([
            'name' => $types[array_rand($types)][0],
            'description' => 'Sampah mudah terurai seperti sisa makanan dan daun.',
            'admin_id' => 2
        ]);

        WasteType::factory()->create([
            'name' => $types[array_rand($types)][0],
            'description' => 'Sampah sulit terurai seperti plastik, botol, dan kaleng.',
            'admin_id' => 3
        ]);

        foreach ($types as [$name, $desc]) {
            WasteType::firstOrCreate([
                'name' => $name,
            ], [
                'description' => $desc,
                'admin_id' => $admin->id
            ]);
        }

        // WasteType::factory(20)->create();
    }
}
