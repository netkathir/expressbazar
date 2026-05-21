<?php

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $country = Country::firstOrCreate(
            ['country_code' => 'IN'],
            [
                'country_name' => 'India',
                'currency' => 'INR',
                'timezone' => 'Asia/Kolkata',
                'status' => 'active',
            ]
        );

        if ($country->status !== 'active') {
            $country->update(['status' => 'active']);
        }

        foreach ($this->indiaCities() as $cityData) {
            City::firstOrCreate(
                [
                    'country_id' => $country->id,
                    'state' => $cityData['state'],
                    'city_name' => $cityData['city_name'],
                ],
                [
                    'city_code' => $cityData['city_code'],
                    'status' => 'active',
                ]
            );
        }
    }

    public function down(): void
    {
        $country = Country::where('country_code', 'IN')->first();

        if (! $country) {
            return;
        }

        foreach ($this->indiaCities() as $cityData) {
            City::where('country_id', $country->id)
                ->where('state', $cityData['state'])
                ->where('city_name', $cityData['city_name'])
                ->where('city_code', $cityData['city_code'])
                ->delete();
        }
    }

    private function indiaCities(): array
    {
        return [
            ['city_name' => 'Mumbai', 'city_code' => 'MUM', 'state' => 'Maharashtra'],
            ['city_name' => 'Delhi', 'city_code' => 'DEL', 'state' => 'Delhi'],
            ['city_name' => 'Bengaluru', 'city_code' => 'BLR', 'state' => 'Karnataka'],
            ['city_name' => 'Chennai', 'city_code' => 'CHE', 'state' => 'Tamil Nadu'],
            ['city_name' => 'Hyderabad', 'city_code' => 'HYD', 'state' => 'Telangana'],
            ['city_name' => 'Kolkata', 'city_code' => 'KOL', 'state' => 'West Bengal'],
            ['city_name' => 'Pune', 'city_code' => 'PUN', 'state' => 'Maharashtra'],
            ['city_name' => 'Ahmedabad', 'city_code' => 'AMD', 'state' => 'Gujarat'],
            ['city_name' => 'Coimbatore', 'city_code' => 'CBE', 'state' => 'Tamil Nadu'],
            ['city_name' => 'Madurai', 'city_code' => 'MDU', 'state' => 'Tamil Nadu'],
            ['city_name' => 'Tiruchirappalli', 'city_code' => 'TRZ', 'state' => 'Tamil Nadu'],
            ['city_name' => 'Salem', 'city_code' => 'SLM', 'state' => 'Tamil Nadu'],
            ['city_name' => 'Erode', 'city_code' => 'ERD', 'state' => 'Tamil Nadu'],
            ['city_name' => 'Tirunelveli', 'city_code' => 'TEN', 'state' => 'Tamil Nadu'],
            ['city_name' => 'Mysuru', 'city_code' => 'MYS', 'state' => 'Karnataka'],
            ['city_name' => 'Mangaluru', 'city_code' => 'IXE', 'state' => 'Karnataka'],
            ['city_name' => 'Hubballi', 'city_code' => 'HBX', 'state' => 'Karnataka'],
            ['city_name' => 'Kochi', 'city_code' => 'COK', 'state' => 'Kerala'],
            ['city_name' => 'Thiruvananthapuram', 'city_code' => 'TRV', 'state' => 'Kerala'],
            ['city_name' => 'Kozhikode', 'city_code' => 'CCJ', 'state' => 'Kerala'],
            ['city_name' => 'Visakhapatnam', 'city_code' => 'VTZ', 'state' => 'Andhra Pradesh'],
            ['city_name' => 'Vijayawada', 'city_code' => 'VGA', 'state' => 'Andhra Pradesh'],
            ['city_name' => 'Warangal', 'city_code' => 'WGL', 'state' => 'Telangana'],
            ['city_name' => 'Nagpur', 'city_code' => 'NAG', 'state' => 'Maharashtra'],
            ['city_name' => 'Nashik', 'city_code' => 'ISK', 'state' => 'Maharashtra'],
            ['city_name' => 'Aurangabad', 'city_code' => 'IXU', 'state' => 'Maharashtra'],
            ['city_name' => 'Surat', 'city_code' => 'STV', 'state' => 'Gujarat'],
            ['city_name' => 'Vadodara', 'city_code' => 'BDQ', 'state' => 'Gujarat'],
            ['city_name' => 'Rajkot', 'city_code' => 'RAJ', 'state' => 'Gujarat'],
            ['city_name' => 'Jaipur', 'city_code' => 'JAI', 'state' => 'Rajasthan'],
            ['city_name' => 'Lucknow', 'city_code' => 'LKO', 'state' => 'Uttar Pradesh'],
            ['city_name' => 'Kanpur', 'city_code' => 'KNU', 'state' => 'Uttar Pradesh'],
            ['city_name' => 'Chandigarh', 'city_code' => 'IXC', 'state' => 'Chandigarh'],
            ['city_name' => 'Bhubaneswar', 'city_code' => 'BBI', 'state' => 'Odisha'],
            ['city_name' => 'Patna', 'city_code' => 'PAT', 'state' => 'Bihar'],
            ['city_name' => 'Guwahati', 'city_code' => 'GAU', 'state' => 'Assam'],
            ['city_name' => 'Shillong', 'city_code' => 'SHL', 'state' => 'Meghalaya'],
        ];
    }
};
