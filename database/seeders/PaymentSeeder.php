<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use Illuminate\Support\Facades\Schema;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Payment::truncate();
        Schema::enableForeignKeyConstraints();

        Payment::factory()->count(50)->create();
    }
}
