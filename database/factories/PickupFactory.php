<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Pickup;
use App\Models\PickupSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Pickup>
 */
final class PickupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Pickup::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'pickup_schedule_id' => PickupSchedule::inRandomOrder()->first()->id,
            'customer_id' => User::where('role', 'customer')->inRandomOrder()->first()->id,
            'petugas_id' => User::where('role', 'petugas')->inRandomOrder()->first()->id,
            'status' => fake()->randomElement(['scheduled', 'assigned', 'completed', 'canceled']),
            'note' => fake()->optional()->sentence,
        ];
    }
}
