<?php

namespace Database\Seeders;

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

        $types = [
            ['Organik', 'Sampah mudah terurai seperti sisa makanan dan daun.'],
            ['Anorganik', 'Sampah sulit terurai seperti plastik, botol, dan kaleng.'],
            ['Elektronik', 'Barang elektronik rusak seperti HP, TV, kabel.'],
            ['B3', 'Bahan Berbahaya & Beracun seperti baterai, oli.'],
        ];

        foreach ($types as [$name, $desc]) {
            WasteType::firstOrCreate([
                'name' => $name,
            ], [
                'description' => $desc,
            ]);
        }
    }
}
