<?php

namespace Database\Seeders;

use App\Models\WasteCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class WasteCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        WasteCategory::truncate();
        Schema::enableForeignKeyConstraints();

        $wasteCategories = [
            [
                'name' => 'Organik',
                'description' => 'Sampah organik adalah sampah yang berasal dari makhluk hidup seperti sisa makanan, daun, ranting, dan lain-lain yang dapat diurai oleh mikroorganisme.',
            ],
            [
                'name' => 'Anorganik',
                'description' => 'Sampah anorganik adalah sampah yang berasal dari benda mati seperti plastik, kertas, logam, dan lain-lain yang tidak dapat diurai oleh mikroorganisme.',
            ],
            [
                'name' => 'Toxic dan Berbahaya',
                'description' => 'Sampah toxic dan berbahaya adalah sampah yang dapat membahayakan lingkungan dan makhluk hidup, seperti baterai, lampu, dan lain-lain.',
            ],
            [
                'name' => 'B3',
                'description' => 'Sampah B3 adalah sampah yang mengandung bahan berbahaya dan beracun, seperti oli, baterai, dan lain-lain.',
            ],
            [
                'name' => 'Lain-Lain',
                'description' => 'Sampah lain-lain adalah sampah yang tidak dapat digolongkan pada kategori di atas, seperti sampah elektronik, dan lain-lain.',
            ],
        ];

        foreach ($wasteCategories as $wasteCategory) {
            WasteCategory::create($wasteCategory);
        }
    }
}
