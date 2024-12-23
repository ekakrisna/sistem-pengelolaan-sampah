<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WasteTransactionDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\WasteTransactionDetail>
 */
final class WasteTransactionDetailFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = WasteTransactionDetail::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'users_id' => \App\Models\User::factory(),
            'waste_transactions_id' => \App\Models\WasteTransaction::factory(),
            'payment_methods_id' => \App\Models\PaymentMethod::factory(),
            'total_price' => fake()->randomFloat(2, 0, 99999999),
            'status' => fake()->optional()->randomElement(['pending', 'completed', 'failed']),
        ];
    }
}
