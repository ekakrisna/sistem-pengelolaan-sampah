<?php

namespace Database\Factories;

use App\Models\PickupSchedule;
use App\Models\WasteType;
use Illuminate\Database\Eloquent\Factories\Factory;

class PickupScheduleFactory extends Factory
{
    protected $model = PickupSchedule::class;

    public function definition(): array
    {
        return [
            'waste_type_id' => WasteType::inRandomOrder()->first()->id ?? 1,
            'date' => $this->faker->dateTimeBetween('+1 days', '+14 days')->format('Y-m-d'),
            'time_slot' => $this->faker->randomElement([
                '07:00 - 09:00',
                '09:00 - 11:00',
                '13:00 - 15:00'
            ]),
            'location' => $this->faker->optional()->sentence,
        ];
    }
}
