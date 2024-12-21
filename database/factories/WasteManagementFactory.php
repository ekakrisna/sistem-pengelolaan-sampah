<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WasteManagement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\WasteManagement>
 */
final class WasteManagementFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = WasteManagement::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'users_id' => fake()->randomNumber(),
            'waste_categories_id' => fake()->randomNumber(),
            'locations_id' => fake()->randomNumber(),
            'packages_id' => fake()->randomNumber(),
            'pickup_time' => fake()->time(),
            'status' => fake()->randomNumber(1),
            'expired_at' => fake()->dateTime(),
        ];
    }
}
