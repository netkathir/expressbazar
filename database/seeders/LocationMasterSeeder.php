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
        $countries = [
            ['code' => 'UK', 'name' => 'United Kingdom', 'currency' => 'GBP', 'timezone' => 'Europe/London'],
            ['code' => 'IN', 'name' => 'India', 'currency' => 'INR', 'timezone' => 'Asia/Kolkata'],
        ];

        $countryModels = [];

        foreach ($countries as $countryData) {
            $countryModels[$countryData['code']] = Country::updateOrCreate(
                ['country_code' => $countryData['code']],
                [
                    'country_name' => $countryData['name'],
                    'currency' => $countryData['currency'],
                    'timezone' => $countryData['timezone'],
                    'status' => 'active',
                ]
            );
        }

        $cities = [
            ['country_code' => 'UK', 'city_code' => 'SOU', 'name' => 'Southampton', 'state' => 'Hampshire'],
            ['country_code' => 'UK', 'city_code' => 'LON', 'name' => 'London', 'state' => 'Greater London'],
            ['country_code' => 'UK', 'city_code' => 'MAN', 'name' => 'Manchester', 'state' => 'Greater Manchester'],
            ['country_code' => 'UK', 'city_code' => 'LIV', 'name' => 'Liverpool', 'state' => 'Merseyside'],
            ['country_code' => 'UK', 'city_code' => 'BIR', 'name' => 'Birmingham', 'state' => 'West Midlands'],
            ['country_code' => 'UK', 'city_code' => 'LEE', 'name' => 'Leeds', 'state' => 'West Yorkshire'],
            ['country_code' => 'IN', 'city_code' => 'MUM', 'name' => 'Mumbai', 'state' => 'Maharashtra'],
            ['country_code' => 'IN', 'city_code' => 'BLR', 'name' => 'Bengaluru', 'state' => 'Karnataka'],
            ['country_code' => 'IN', 'city_code' => 'DEL', 'name' => 'Delhi', 'state' => 'Delhi'],
            ['country_code' => 'IN', 'city_code' => 'CHE', 'name' => 'Chennai', 'state' => 'Tamil Nadu'],
            ['country_code' => 'IN', 'city_code' => 'PUD', 'name' => 'Puducherry', 'state' => 'Puducherry'],
            ['country_code' => 'IN', 'city_code' => 'HYD', 'name' => 'Hyderabad', 'state' => 'Telangana'],
            ['country_code' => 'IN', 'city_code' => 'KOL', 'name' => 'Kolkata', 'state' => 'West Bengal'],
        ];

        $cityModels = [];

        foreach ($cities as $cityData) {
            $country = $countryModels[$cityData['country_code']] ?? null;

            if (! $country) {
                continue;
            }

            $cityModels[$cityData['city_code']] = City::updateOrCreate(
                ['country_id' => $country->id, 'city_code' => $cityData['city_code']],
                [
                    'state' => $cityData['state'],
                    'city_name' => $cityData['name'],
                    'status' => 'active',
                ]
            );
        }

        $zones = [
            ['city_code' => 'SOU', 'zone_code' => 'SO16', 'name' => 'SO16 Area'],
            ['city_code' => 'SOU', 'zone_code' => 'SO14', 'name' => 'SO14 Area'],
            ['city_code' => 'LON', 'zone_code' => 'SW1', 'name' => 'Westminster'],
            ['city_code' => 'LON', 'zone_code' => 'E1', 'name' => 'East London'],
            ['city_code' => 'MAN', 'zone_code' => 'MCR1', 'name' => 'Manchester Central'],
            ['city_code' => 'LIV', 'zone_code' => 'LIV1', 'name' => 'Liverpool City Centre'],
            ['city_code' => 'BIR', 'zone_code' => 'BIR1', 'name' => 'Birmingham Central'],
            ['city_code' => 'LEE', 'zone_code' => 'LEE1', 'name' => 'Leeds City Centre'],
            ['city_code' => 'MUM', 'zone_code' => 'MU01', 'name' => 'South Mumbai'],
            ['city_code' => 'MUM', 'zone_code' => 'MU02', 'name' => 'Andheri'],
            ['city_code' => 'BLR', 'zone_code' => 'BLR1', 'name' => 'Indiranagar'],
            ['city_code' => 'BLR', 'zone_code' => 'BLR2', 'name' => 'Whitefield'],
            ['city_code' => 'DEL', 'zone_code' => 'DEL1', 'name' => 'New Delhi'],
            ['city_code' => 'CHE', 'zone_code' => 'CHE1', 'name' => 'Chennai Central'],
            ['city_code' => 'PUD', 'zone_code' => 'PUD1', 'name' => 'Puducherry Central'],
            ['city_code' => 'HYD', 'zone_code' => 'HYD1', 'name' => 'Hyderabad Central'],
            ['city_code' => 'KOL', 'zone_code' => 'KOL1', 'name' => 'Kolkata Central'],
        ];

        foreach ($zones as $zoneData) {
            $city = $cityModels[$zoneData['city_code']] ?? null;
            $country = $city?->country;

            if (! $city || ! $country) {
                continue;
            }

            RegionZone::updateOrCreate(
                ['city_id' => $city->id, 'zone_code' => $zoneData['zone_code']],
                [
                    'country_id' => $country->id,
                    'zone_name' => $zoneData['name'],
                    'delivery_available' => true,
                    'status' => 'active',
                ]
            );
        }
    }
}
