<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserAddress;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\UserAddress>
 */
final class UserAddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $village = Village::with(['district.city.province'])->inRandomOrder()->first();
        $district = $village->district;
        $city = $district->city;
        $province = $city->province;

        return [
            'user_id' => User::whereNotIn('role', ['admin', 'petugas', 'super_admin'])->inRandomOrder()->first()->id,
            'province_code' => $province->code,
            'city_code' => $city->code,
            'district_code' => $district->code,
            'village_code' => $village->code,
            'label' => fake()->optional()->word,
            'address_detail' => fake()->optional()->word,
            'lat' => fake()->optional()->randomFloat(7, 0, 999),
            'lng' => fake()->optional()->randomFloat(7, 0, 999),
            'is_default' => fake()->randomNumber(1),
            'meta' => fake()->optional()->word,
        ];
    }
}
