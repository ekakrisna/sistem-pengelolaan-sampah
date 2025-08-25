<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Transaction>
 */
final class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'customer_id' => User::inRandomOrder()->first()->id,
            'number' => fake()->optional()->word,
            'status' => fake()->randomElement(['draft', 'pending', 'paid', 'partially_paid', 'expired', 'canceled', 'refunded']),
            'subtotal' => fake()->randomFloat(2, 0, 9999999999),
            'discount_amount' => fake()->randomFloat(2, 0, 9999999999),
            'tax_amount' => fake()->randomFloat(2, 0, 9999999999),
            'total' => fake()->randomFloat(2, 0, 9999999999),
            'currency' => fake()->currencyCode,
            'due_at' => fake()->optional()->datetime(),
            'expires_at' => fake()->optional()->datetime(),
            'description' => fake()->optional()->text,
            'meta' => fake()->optional()->word,
        ];
    }
}
