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
            ['country_code' => 'IN', 'city_code' => 'PUN', 'name' => 'Pune', 'state' => 'Maharashtra'],
            ['country_code' => 'IN', 'city_code' => 'AMD', 'name' => 'Ahmedabad', 'state' => 'Gujarat'],
            ['country_code' => 'IN', 'city_code' => 'CBE', 'name' => 'Coimbatore', 'state' => 'Tamil Nadu'],
            ['country_code' => 'IN', 'city_code' => 'MDU', 'name' => 'Madurai', 'state' => 'Tamil Nadu'],
            ['country_code' => 'IN', 'city_code' => 'TRZ', 'name' => 'Tiruchirappalli', 'state' => 'Tamil Nadu'],
            ['country_code' => 'IN', 'city_code' => 'SLM', 'name' => 'Salem', 'state' => 'Tamil Nadu'],
            ['country_code' => 'IN', 'city_code' => 'ERD', 'name' => 'Erode', 'state' => 'Tamil Nadu'],
            ['country_code' => 'IN', 'city_code' => 'TEN', 'name' => 'Tirunelveli', 'state' => 'Tamil Nadu'],
            ['country_code' => 'IN', 'city_code' => 'MYS', 'name' => 'Mysuru', 'state' => 'Karnataka'],
            ['country_code' => 'IN', 'city_code' => 'IXE', 'name' => 'Mangaluru', 'state' => 'Karnataka'],
            ['country_code' => 'IN', 'city_code' => 'HBX', 'name' => 'Hubballi', 'state' => 'Karnataka'],
            ['country_code' => 'IN', 'city_code' => 'COK', 'name' => 'Kochi', 'state' => 'Kerala'],
            ['country_code' => 'IN', 'city_code' => 'TRV', 'name' => 'Thiruvananthapuram', 'state' => 'Kerala'],
            ['country_code' => 'IN', 'city_code' => 'CCJ', 'name' => 'Kozhikode', 'state' => 'Kerala'],
            ['country_code' => 'IN', 'city_code' => 'VTZ', 'name' => 'Visakhapatnam', 'state' => 'Andhra Pradesh'],
            ['country_code' => 'IN', 'city_code' => 'VGA', 'name' => 'Vijayawada', 'state' => 'Andhra Pradesh'],
            ['country_code' => 'IN', 'city_code' => 'WGL', 'name' => 'Warangal', 'state' => 'Telangana'],
            ['country_code' => 'IN', 'city_code' => 'NAG', 'name' => 'Nagpur', 'state' => 'Maharashtra'],
            ['country_code' => 'IN', 'city_code' => 'ISK', 'name' => 'Nashik', 'state' => 'Maharashtra'],
            ['country_code' => 'IN', 'city_code' => 'IXU', 'name' => 'Aurangabad', 'state' => 'Maharashtra'],
            ['country_code' => 'IN', 'city_code' => 'STV', 'name' => 'Surat', 'state' => 'Gujarat'],
            ['country_code' => 'IN', 'city_code' => 'BDQ', 'name' => 'Vadodara', 'state' => 'Gujarat'],
            ['country_code' => 'IN', 'city_code' => 'RAJ', 'name' => 'Rajkot', 'state' => 'Gujarat'],
            ['country_code' => 'IN', 'city_code' => 'JAI', 'name' => 'Jaipur', 'state' => 'Rajasthan'],
            ['country_code' => 'IN', 'city_code' => 'LKO', 'name' => 'Lucknow', 'state' => 'Uttar Pradesh'],
            ['country_code' => 'IN', 'city_code' => 'KNU', 'name' => 'Kanpur', 'state' => 'Uttar Pradesh'],
            ['country_code' => 'IN', 'city_code' => 'IXC', 'name' => 'Chandigarh', 'state' => 'Chandigarh'],
            ['country_code' => 'IN', 'city_code' => 'BBI', 'name' => 'Bhubaneswar', 'state' => 'Odisha'],
            ['country_code' => 'IN', 'city_code' => 'PAT', 'name' => 'Patna', 'state' => 'Bihar'],
            ['country_code' => 'IN', 'city_code' => 'GAU', 'name' => 'Guwahati', 'state' => 'Assam'],
            ['country_code' => 'IN', 'city_code' => 'SHL', 'name' => 'Shillong', 'state' => 'Meghalaya'],
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
