<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserAddress;
use App\Models\Village;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class UserAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        UserAddress::truncate();
        Schema::enableForeignKeyConstraints();

        $village = Village::with(['district.city.province'])->findOrFail('1101012001');
        $customer = User::where('email', 'customer@lokabersih.com')->firstOrFail();

        UserAddress::factory()->create([
            'user_id'       => $customer->id,
            'village_code'  => $village->code,
            'district_code' => $village->district->code,
            'city_code'     => $village->district->city->code,
            'province_code' => $village->district->city->province->code,
        ]);

        $village = Village::with(['district.city.province'])->findOrFail('1101012002');
        $customer = User::where('email', 'customer@lokabersih.com')->firstOrFail();

        UserAddress::factory()->create([
            'user_id'       => $customer->id,
            'village_code'  => $village->code,
            'district_code' => $village->district->code,
            'city_code'     => $village->district->city->code,
            'province_code' => $village->district->city->province->code,
        ]);

        UserAddress::factory()->count(40)->create();
    }
}
