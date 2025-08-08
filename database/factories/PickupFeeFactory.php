<?php

namespace Database\Factories;

use App\Models\PickupFee;
use App\Models\User;
use App\Models\Village;
use App\Models\WasteType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PickupFee>
 */
class PickupFeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = PickupFee::class;

    public function definition(): array
    {
        return [
            'village_code' => Village::inRandomOrder()->first()->code,
            'waste_type_id' => WasteType::inRandomOrder()->first()->id,
            'admin_id' => User::inRandomOrder()->first()->id,
            'amount' => $this->faker->numberBetween(1, 5) * 10000, // Rounded to the nearest 10,000, between Rp 5.000.000 and Rp 50.000.000
            'description' => $this->faker->sentence(),
        ];
    }
}
