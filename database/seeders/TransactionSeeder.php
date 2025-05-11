<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use Illuminate\Support\Facades\Schema;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Transaction::truncate();
        Schema::enableForeignKeyConstraints();

        Transaction::factory()->count(40)->create();
    }
}
