<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = User::class;


    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Pilih provinsi acak
        $province = Province::with(['cities.districts.villages'])->inRandomOrder()->first();

        // Ambil city berdasarkan provinsi
        $city = $province->cities->random();

        // Ambil district berdasarkan city
        $district = $city->districts->random();

        // Ambil village berdasarkan district
        $village = $district->villages->random();

        // dd($village);

        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => '08' . $this->faker->numerify('##########'),
            'password' => bcrypt('password'),
            'role' => $this->faker->randomElement(['admin', 'petugas', 'customer', 'super_admin']),

            'province_id' => $province->code,
            'city_id' => $city->code,
            'district_id' => $district->code,
            'village_id' => $village->code,

            'address_detail' => $this->faker->streetAddress . ', ' . $this->faker->citySuffix,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
