<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\RegionZone;
use Illuminate\Database\Seeder;

class LocationMasterSeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::updateOrCreate(
            ['country_code' => 'UK'],
            [
                'country_name' => 'United Kingdom',
                'currency' => 'GBP',
                'timezone' => 'Europe/London',
                'status' => 'active',
            ]
        );

        $city = City::updateOrCreate(
            ['country_id' => $country->id, 'city_name' => 'Southampton'],
            [
                'state' => 'Hampshire',
                'city_code' => 'SOU',
                'status' => 'active',
            ]
        );

        RegionZone::updateOrCreate(
            ['city_id' => $city->id, 'zone_name' => 'SO16 Area'],
            [
                'country_id' => $country->id,
                'zone_code' => 'SO16',
                'delivery_available' => true,
                'status' => 'active',
            ]
        );
    }
}
