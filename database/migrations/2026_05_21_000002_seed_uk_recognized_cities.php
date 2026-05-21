<?php

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $country = Country::firstOrCreate(
            ['country_code' => 'UK'],
            [
                'country_name' => 'United Kingdom',
                'currency' => 'GBP',
                'timezone' => 'Europe/London',
                'status' => 'active',
            ]
        );

        if ($country->status !== 'active') {
            $country->update(['status' => 'active']);
        }

        foreach ($this->ukCities() as $cityData) {
            $query = City::where('country_id', $country->id)
                ->where('city_name', $cityData['city_name']);

            if ($cityData['city_name'] !== 'Bangor' && $query->exists()) {
                continue;
            }

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
        $country = Country::where('country_code', 'UK')->first();

        if (! $country) {
            return;
        }

        foreach ($this->ukCities() as $cityData) {
            City::where('country_id', $country->id)
                ->where('state', $cityData['state'])
                ->where('city_name', $cityData['city_name'])
                ->where('city_code', $cityData['city_code'])
                ->delete();
        }
    }

    private function ukCities(): array
    {
        return [
            ['city_name' => 'Bath', 'city_code' => 'BATH', 'state' => 'England'],
            ['city_name' => 'Birmingham', 'city_code' => 'BIR', 'state' => 'England'],
            ['city_name' => 'Bradford', 'city_code' => 'BRD', 'state' => 'England'],
            ['city_name' => 'Brighton & Hove', 'city_code' => 'BTN', 'state' => 'England'],
            ['city_name' => 'Bristol', 'city_code' => 'BRS', 'state' => 'England'],
            ['city_name' => 'Cambridge', 'city_code' => 'CBG', 'state' => 'England'],
            ['city_name' => 'Canterbury', 'city_code' => 'CTB', 'state' => 'England'],
            ['city_name' => 'Carlisle', 'city_code' => 'CAR', 'state' => 'England'],
            ['city_name' => 'Chelmsford', 'city_code' => 'CHM', 'state' => 'England'],
            ['city_name' => 'Chester', 'city_code' => 'CHS', 'state' => 'England'],
            ['city_name' => 'Chichester', 'city_code' => 'CCT', 'state' => 'England'],
            ['city_name' => 'Colchester', 'city_code' => 'COL', 'state' => 'England'],
            ['city_name' => 'Coventry', 'city_code' => 'COV', 'state' => 'England'],
            ['city_name' => 'Derby', 'city_code' => 'DER', 'state' => 'England'],
            ['city_name' => 'Doncaster', 'city_code' => 'DON', 'state' => 'England'],
            ['city_name' => 'Durham', 'city_code' => 'DUR', 'state' => 'England'],
            ['city_name' => 'Ely', 'city_code' => 'ELY', 'state' => 'England'],
            ['city_name' => 'Exeter', 'city_code' => 'EXT', 'state' => 'England'],
            ['city_name' => 'Gloucester', 'city_code' => 'GLO', 'state' => 'England'],
            ['city_name' => 'Hereford', 'city_code' => 'HER', 'state' => 'England'],
            ['city_name' => 'Kingston-upon-Hull', 'city_code' => 'HUL', 'state' => 'England'],
            ['city_name' => 'Lancaster', 'city_code' => 'LAN', 'state' => 'England'],
            ['city_name' => 'Leeds', 'city_code' => 'LEE', 'state' => 'England'],
            ['city_name' => 'Leicester', 'city_code' => 'LEI', 'state' => 'England'],
            ['city_name' => 'Lichfield', 'city_code' => 'LIC', 'state' => 'England'],
            ['city_name' => 'Lincoln', 'city_code' => 'LIN', 'state' => 'England'],
            ['city_name' => 'Liverpool', 'city_code' => 'LIV', 'state' => 'England'],
            ['city_name' => 'London', 'city_code' => 'LON', 'state' => 'England'],
            ['city_name' => 'Manchester', 'city_code' => 'MAN', 'state' => 'England'],
            ['city_name' => 'Milton Keynes', 'city_code' => 'MKY', 'state' => 'England'],
            ['city_name' => 'Newcastle-upon-Tyne', 'city_code' => 'NCL', 'state' => 'England'],
            ['city_name' => 'Norwich', 'city_code' => 'NOR', 'state' => 'England'],
            ['city_name' => 'Nottingham', 'city_code' => 'NOT', 'state' => 'England'],
            ['city_name' => 'Oxford', 'city_code' => 'OXF', 'state' => 'England'],
            ['city_name' => 'Peterborough', 'city_code' => 'PBO', 'state' => 'England'],
            ['city_name' => 'Plymouth', 'city_code' => 'PLY', 'state' => 'England'],
            ['city_name' => 'Portsmouth', 'city_code' => 'POR', 'state' => 'England'],
            ['city_name' => 'Preston', 'city_code' => 'PRE', 'state' => 'England'],
            ['city_name' => 'Ripon', 'city_code' => 'RIP', 'state' => 'England'],
            ['city_name' => 'Salford', 'city_code' => 'SAL', 'state' => 'England'],
            ['city_name' => 'Salisbury', 'city_code' => 'SLS', 'state' => 'England'],
            ['city_name' => 'Sheffield', 'city_code' => 'SHF', 'state' => 'England'],
            ['city_name' => 'Southampton', 'city_code' => 'SOU', 'state' => 'England'],
            ['city_name' => 'Southend-on-Sea', 'city_code' => 'SOS', 'state' => 'England'],
            ['city_name' => 'St Albans', 'city_code' => 'STA', 'state' => 'England'],
            ['city_name' => 'Stoke on Trent', 'city_code' => 'SOT', 'state' => 'England'],
            ['city_name' => 'Sunderland', 'city_code' => 'SUN', 'state' => 'England'],
            ['city_name' => 'Truro', 'city_code' => 'TRU', 'state' => 'England'],
            ['city_name' => 'Wakefield', 'city_code' => 'WAK', 'state' => 'England'],
            ['city_name' => 'Wells', 'city_code' => 'WEL', 'state' => 'England'],
            ['city_name' => 'Westminster', 'city_code' => 'WES', 'state' => 'England'],
            ['city_name' => 'Winchester', 'city_code' => 'WIN', 'state' => 'England'],
            ['city_name' => 'Wolverhampton', 'city_code' => 'WLV', 'state' => 'England'],
            ['city_name' => 'Worcester', 'city_code' => 'WOR', 'state' => 'England'],
            ['city_name' => 'York', 'city_code' => 'YRK', 'state' => 'England'],
            ['city_name' => 'Armagh', 'city_code' => 'ARM', 'state' => 'Northern Ireland'],
            ['city_name' => 'Bangor', 'city_code' => 'BNI', 'state' => 'Northern Ireland'],
            ['city_name' => 'Belfast', 'city_code' => 'BFS', 'state' => 'Northern Ireland'],
            ['city_name' => 'Lisburn', 'city_code' => 'LSB', 'state' => 'Northern Ireland'],
            ['city_name' => 'Londonderry', 'city_code' => 'LDY', 'state' => 'Northern Ireland'],
            ['city_name' => 'Newry', 'city_code' => 'NRY', 'state' => 'Northern Ireland'],
            ['city_name' => 'Aberdeen', 'city_code' => 'ABD', 'state' => 'Scotland'],
            ['city_name' => 'Dundee', 'city_code' => 'DND', 'state' => 'Scotland'],
            ['city_name' => 'Dunfermline', 'city_code' => 'DFM', 'state' => 'Scotland'],
            ['city_name' => 'Edinburgh', 'city_code' => 'EDI', 'state' => 'Scotland'],
            ['city_name' => 'Glasgow', 'city_code' => 'GLA', 'state' => 'Scotland'],
            ['city_name' => 'Inverness', 'city_code' => 'INV', 'state' => 'Scotland'],
            ['city_name' => 'Perth', 'city_code' => 'PER', 'state' => 'Scotland'],
            ['city_name' => 'Stirling', 'city_code' => 'STI', 'state' => 'Scotland'],
            ['city_name' => 'Bangor', 'city_code' => 'BGW', 'state' => 'Wales'],
            ['city_name' => 'Cardiff', 'city_code' => 'CDF', 'state' => 'Wales'],
            ['city_name' => 'Newport', 'city_code' => 'NWP', 'state' => 'Wales'],
            ['city_name' => 'St Asaph', 'city_code' => 'SAS', 'state' => 'Wales'],
            ['city_name' => 'St Davids', 'city_code' => 'STD', 'state' => 'Wales'],
            ['city_name' => 'Swansea', 'city_code' => 'SWA', 'state' => 'Wales'],
            ['city_name' => 'Wrexham', 'city_code' => 'WRX', 'state' => 'Wales'],
        ];
    }
};
