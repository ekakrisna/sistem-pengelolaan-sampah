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
            'amount' => $this->faker->randomFloat(2, 5000, 50000), // Rp 5.000 - Rp 50.000
        ];
    }
}
