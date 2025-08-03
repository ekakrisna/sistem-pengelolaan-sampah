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
            'pickup_id' => Pickup::factory(),
            'payment_id' => Payment::factory(),
            'total' => $this->faker->randomFloat(2, 10000, 50000),
            'description' => $this->faker->sentence(),
        ];
    }
}
