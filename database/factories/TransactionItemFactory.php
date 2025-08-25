<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\TransactionItem>
 */
final class TransactionItemFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = TransactionItem::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'transaction_id' => \App\Models\Transaction::factory(),
            'item_type' => fake()->randomElement(['pickup', 'surcharge', 'discount', 'tax', 'other']),
            'user_address_id' => \App\Models\UserAddress::factory(),
            'pickup_schedule_id' => \App\Models\PickupSchedule::factory(),
            'pickup_fee_id' => \App\Models\PickupFee::factory(),
            'pickup_id' => \App\Models\Pickup::factory(),
            'description' => fake()->optional()->text,
            'unit_amount' => fake()->randomFloat(2, 0, 9999999999),
            'qty' => fake()->randomNumber(),
            'line_total' => fake()->randomFloat(2, 0, 9999999999),
            'meta' => fake()->optional()->word,
        ];
    }
}
