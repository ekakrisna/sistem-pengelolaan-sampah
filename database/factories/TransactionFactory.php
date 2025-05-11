<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Pickup;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $pickup = Pickup::inRandomOrder()->first();
        $payment = Payment::inRandomOrder()->first();

        return [
            'pickup_id' => $pickup->id ?? 1,
            'payment_id' => $payment->id ?? 1,
        ];
    }
}
