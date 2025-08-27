<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PickupFee;
use App\Models\User;
use App\Models\Village;
use App\Models\WasteType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\PickupFee>
 */
final class PickupFeeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PickupFee::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'village_code' => Village::inRandomOrder()->first()->code,
            'waste_type_id' => WasteType::inRandomOrder()->first()->id,
            'admin_id' => User::where('role', 'admin')->inRandomOrder()->first()->id,
            'amount' => fake()->numberBetween(1, 10) * 1000,
            'description' => fake('id')->optional()->text,
        ];
    }
}
