<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Schedule>
 */
final class ScheduleFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Schedule::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'waste_categories_id' => \App\Models\WasteCategory::factory(),
            'locations_id' => \App\Models\Location::factory(),
            'colectors_id' => \App\Models\User::factory(),
            'pickup_date' => fake()->optional()->date(),
            'pickup_time' => fake()->optional()->date(),
            'status' => fake()->optional()->randomNumber(1),
        ];
    }
}
