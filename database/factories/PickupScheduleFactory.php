<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PickupSchedule;
use App\Models\User;
use App\Models\Village;
use App\Models\WasteType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\PickupSchedule>
 */
final class PickupScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PickupSchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'admin_id' => User::where('role', 'admin')->inRandomOrder()->first()->id,
            'waste_type_id' => WasteType::inRandomOrder()->first()->id,
            'day_of_week' => fake()->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
            'start_pickup_time' => fake()->time(),
            'end_pickup_time' => fake()->time(),
            'village_code' => Village::inRandomOrder()->first()->code,
            'quota' => fake()->randomNumber(),
        ];
    }
}
