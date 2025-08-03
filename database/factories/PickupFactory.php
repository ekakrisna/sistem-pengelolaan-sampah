<?php

namespace Database\Factories;

use App\Models\Pickup;
use App\Models\PickupSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PickupFactory extends Factory
{
    protected $model = Pickup::class;

    public function definition(): array
    {
        // Ambil satu jadwal
        $schedule = PickupSchedule::inRandomOrder()->first();

        // Ambil customer dan petugas
        $customer = User::where('role', 'customer')->inRandomOrder()->first();
        $petugas = User::where('role', 'petugas')->inRandomOrder()->first();

        return [
            'pickup_schedule_id' => $schedule->id ?? 1,
            'customer_id' => $customer->id ?? 1,
            'petugas_id' => $petugas->id ?? null,

            'status' => $this->faker->randomElement(['scheduled', 'completed', 'canceled']),
            'note' => $this->faker->optional()->sentence,
        ];
    }
}
