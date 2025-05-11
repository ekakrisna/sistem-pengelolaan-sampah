<?php

namespace Database\Factories;

use App\Models\WasteType;
use Illuminate\Database\Eloquent\Factories\Factory;

class WasteTypeFactory extends Factory
{
    protected $model = WasteType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
            'description' => $this->faker->sentence,
        ];
    }
}
