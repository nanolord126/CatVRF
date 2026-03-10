<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Filter;

class CategorySystemSeeder extends Seeder
{
    public function run(): void
    {
        $verticals = [
            'Beauty' => ['Cosmetics', 'Hair Care', 'Perfume', 'Spa'],
            'RealEstate' => ['Apartments', 'Houses', 'Commercial', 'Land'],
            'Electronics' => ['Smartphones', 'Laptops', 'TV', 'Appliances'],
            'Auto' => ['Cars', 'Trucks', 'Motorcycles', 'Parts'],
            'Restaurants' => ['Fast Food', 'Fine Dining', 'Coffee Shops', 'Bars'],
            'Hotels' => ['Resorts', 'Hostels', 'Business Hotels', 'Apart-hotels'],
        ];

        foreach ($verticals as $v => $cats) {
            foreach ($cats as $c) {
                Category::create([
                    'name' => $c, 'slug' => (string) str($c)->slug(),
                    'vertical' => $v, 'is_active' => true
                ]);
            }
        }

        Brand::create(['name' => 'L\'Oreal', 'slug' => 'loreal', 'country' => 'France']);
        Brand::create(['name' => 'Bosch', 'slug' => 'bosch', 'country' => 'Germany']);
        Brand::create(['name' => 'Toyota', 'slug' => 'toyota', 'country' => 'Japan']);
        Brand::create(['name' => 'Samsung', 'slug' => 'samsung', 'country' => 'Korea']);

        Filter::create(['name' => 'Color', 'vertical' => 'Beauty', 'type' => 'color']);
        Filter::create(['name' => 'Square', 'vertical' => 'RealEstate', 'type' => 'range', 'unit' => 'm2']);
    }
}
