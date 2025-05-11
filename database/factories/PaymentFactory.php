<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'paid', 'failed']);

        return [
            'customer_id' => User::where('role', 'customer')->inRandomOrder()->first()->id ?? 1,
            'amount' => $this->faker->randomElement([10000, 25000, 50000]),
            'status' => $status,
            'payment_method' => $this->faker->randomElement(['qris', 'transfer']),
            'proof_image' => $status !== 'pending' ? $this->faker->imageUrl() : null,
            'paid_at' => $status === 'paid' ? $this->faker->dateTimeBetween('-7 days', 'now') : null,
        ];
    }
}
