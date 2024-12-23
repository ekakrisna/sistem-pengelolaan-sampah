<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        PaymentMethod::truncate();
        Schema::enableForeignKeyConstraints();

        $paymentMethods = [
            ['name' => 'Tunai'],
            ['name' => 'Transfer Bank'],
            ['name' => 'Kartu Kredit'],
            ['name' => 'Dompet Digital'],
            ['name' => 'Cek'],
        ];

        foreach ($paymentMethods as $paymentMethod) {
            PaymentMethod::create($paymentMethod);
        }
    }
}
