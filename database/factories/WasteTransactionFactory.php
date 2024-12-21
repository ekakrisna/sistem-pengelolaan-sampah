<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WasteTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\WasteTransaction>
 */
final class WasteTransactionFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = WasteTransaction::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'waste_managements_id' => \App\Models\WasteManagement::factory(),
            'sub_total' => fake()->optional()->randomFloat(2, 0, 99999999),
        ];
    }
}
