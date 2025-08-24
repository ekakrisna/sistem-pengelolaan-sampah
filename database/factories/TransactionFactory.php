<?php

namespace Database\Factories;

use App\Enums\StatusTransactionEnum;
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

        return [
            'pickup_id' => $pickup ? $pickup->id : null,
            'status' => $this->faker->randomElement(StatusTransactionEnum::values()),
            'total' => $this->faker->randomFloat(2, 10000, 50000),
            'description' => $this->faker->sentence(),
        ];
    }
}
