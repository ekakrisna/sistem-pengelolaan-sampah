<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class IndonesiaLocationsSeeder extends Seeder
{
    private $baseUrl = 'https://dev.farizdotid.com/api/daerahindonesia';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->truncateTables();
        $this->seedProvinces();
    }

    private function truncateTables()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        DB::table('villages')->truncate();
        DB::table('districts')->truncate();
        DB::table('regencies')->truncate();
        DB::table('provinces')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $this->command->info('Tables truncated successfully.');
    }

    private function seedProvinces()
    {
        $response = Http::get("{$this->baseUrl}/provinsi");

        if ($response->failed()) {
            $this->command->error('Failed to fetch provinces from API.');
            return;
        }

        $provinces = $response->json('provinsi');
        foreach ($provinces as $province) {
            DB::table('provinces')->updateOrInsert(
                ['id' => $province['id']],
                ['name' => $province['nama']]
            );

            $this->seedRegencies($province['id']);
        }

        $this->command->info('Provinces seeded successfully.');
    }

    private function seedRegencies($provinceId)
    {
        $response = Http::get("{$this->baseUrl}/kota?id_provinsi={$provinceId}");

        if ($response->failed()) {
            $this->command->error("Failed to fetch regencies for province ID {$provinceId}.");
            return;
        }

        $regencies = $response->json('kota_kabupaten');
        foreach ($regencies as $regency) {
            DB::table('regencies')->updateOrInsert(
                ['id' => $regency['id']],
                [
                    'province_id' => $provinceId,
                    'name' => $regency['nama']
                ]
            );

            $this->seedDistricts($regency['id']);
        }

        $this->command->info("Regencies for province ID {$provinceId} seeded successfully.");
    }

    private function seedDistricts($regencyId)
    {
        $response = Http::get("{$this->baseUrl}/kecamatan?id_kota={$regencyId}");

        if ($response->failed()) {
            $this->command->error("Failed to fetch districts for regency ID {$regencyId}.");
            return;
        }

        $districts = $response->json('kecamatan');
        foreach ($districts as $district) {
            DB::table('districts')->updateOrInsert(
                ['id' => $district['id']],
                [
                    'regency_id' => $regencyId,
                    'name' => $district['nama']
                ]
            );

            $this->seedVillages($district['id']);
        }

        $this->command->info("Districts for regency ID {$regencyId} seeded successfully.");
    }

    private function seedVillages($districtId)
    {
        $response = Http::get("{$this->baseUrl}/kelurahan?id_kecamatan={$districtId}");

        if ($response->failed()) {
            $this->command->error("Failed to fetch villages for district ID {$districtId}.");
            return;
        }

        $villages = $response->json('kelurahan');
        foreach ($villages as $village) {
            DB::table('villages')->updateOrInsert(
                ['id' => $village['id']],
                [
                    'district_id' => $districtId,
                    'name' => $village['nama']
                ]
            );
        }

        $this->command->info("Villages for district ID {$districtId} seeded successfully.");
    }
}
