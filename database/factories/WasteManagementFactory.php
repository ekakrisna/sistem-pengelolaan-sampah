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
            'users_id' => \App\Models\User::factory(),
            'packages_id' => \App\Models\Package::factory(),
            'locations_id' => \App\Models\Location::factory(),
            'pickup_schedule' => fake()->optional()->randomElement(['daily', 'weekly']),
            'pickup_time' => fake()->time(),
            'status' => fake()->randomNumber(1),
            'expired_at' => fake()->dateTime(),
        ];
    }
}
