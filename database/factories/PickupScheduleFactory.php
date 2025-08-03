<?php

namespace Database\Factories;

use App\Models\PickupSchedule;
use App\Models\User;
use App\Models\Village;
use App\Models\WasteType;
use Illuminate\Database\Eloquent\Factories\Factory;

class PickupScheduleFactory extends Factory
{
    protected $model = PickupSchedule::class;

    public function definition(): array
    {
        return [
            'waste_type_id' => WasteType::inRandomOrder()->first()->id ?? 1,
            'admin_id' => User::where('role', 'admin')->inRandomOrder()->first()->id ?? 1,
            'day_of_week' => $this->faker->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
            'start_pickup_time' => $this->faker->time(),
            'end_pickup_time' => $this->faker->time(),
            'code_village' => Village::inRandomOrder()->first()->code,
        ];
    }
}
