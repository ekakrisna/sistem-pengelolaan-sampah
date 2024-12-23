<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Location>
 */
final class LocationFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Location::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'users_id' => \App\Models\User::factory(),
            'villages_id' => \App\Models\Village::factory(),
            'name' => fake()->name,
            'latitude' => fake()->optional()->latitude,
            'longitude' => fake()->optional()->longitude,
        ];
    }
}
