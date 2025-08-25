<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WasteType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\WasteType>
 */
final class WasteTypeFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = WasteType::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'admin_id' => \App\Models\User::factory(),
            'name' => fake()->name,
            'description' => fake()->optional()->text,
        ];
    }
}
