<?php

namespace Database\Seeders;

use App\Domains\Geo\Models\{Country, Region, City, District};
use Illuminate\Database\Seeder;

class GeoHierarchySeeder extends Seeder {
    public function run(): void {
        $data = [
            'Russia' => ['Moscow' => ['Moscow'], 'St Petersburg' => ['St Petersburg'], 'Kazan' => ['Kazan']],
            'Kazakhstan' => ['Astana' => ['Astana'], 'Almaty' => ['Almaty'], 'Shymkent' => ['Shymkent']],
            'Belarus' => ['Minsk' => ['Minsk'], 'Brest' => ['Brest'], 'Gomel' => ['Gomel']],
        ];

        foreach ($data as $cName => $regions) {
            $country = Country::create(['name' => $cName, 'code' => strtoupper($cName[0].$cName[1])]);
            foreach ($regions as $rName => $cities) {
                $region = $country->regions()->create(['name' => $rName]);
                foreach ($cities as $cityName) {
                    $region->districts()->create(['name' => 'City District'])
                           ->cities()->create(['name' => $cityName]);
                }
            }
        }
    }
}
