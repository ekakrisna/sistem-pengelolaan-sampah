<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\District>
 */
final class DistrictFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = District::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'id' => fake()->word,
            'regency_id' => \App\Models\Regency::factory(),
            'name' => fake()->name,
        ];
    }
}
