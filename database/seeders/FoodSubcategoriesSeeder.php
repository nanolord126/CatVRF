<?php

namespace Database\Seeders;

use App\Models\FoodVenue;
use Illuminate\Database\Seeder;

class FoodSubcategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $branches = ['grand-hotel', 'spa-beauty'];
        
        foreach ($branches as $tenantId) {
            tenancy()->initialize($tenantId);
            
            FoodVenue::firstOrCreate(['name' => 'Italiano Vero'], [
                'sub_type' => 'cuisine',
                'cuisine_type' => 'italian',
            ]);

            FoodVenue::firstOrCreate(['name' => 'Cyber Coffee 2026'], [
                'sub_type' => 'coffee',
            ]);
            
            tenancy()->end();
        }
    }
}
