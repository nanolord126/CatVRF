<?php

namespace Database\Seeders;

use App\Models\Domains\RealEstate\Property;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $properties = [
            ['title' => 'Modern Apartment Downtown', 'address' => '100 City Center', 'bedrooms' => 2, 'bathrooms' => 1, 'area_sqm' => 75, 'price_per_night' => 150],
            ['title' => 'Beach House', 'address' => '200 Oceanfront', 'bedrooms' => 3, 'bathrooms' => 2, 'area_sqm' => 120, 'price_per_night' => 250],
            ['title' => 'Studio Loft', 'address' => '300 Arts District', 'bedrooms' => 1, 'bathrooms' => 1, 'area_sqm' => 50, 'price_per_night' => 100],
        ];

        foreach ($properties as $property) {
            Property::factory()->create($property);
        }
    }
}
