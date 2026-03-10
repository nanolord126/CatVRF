<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BaseFilterSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HospitalityAndFoodFiltersSeeder::class,
            HealthAndBeautyFiltersSeeder::class,
            RetailAndGoodsFiltersSeeder::class,
            ProfessionalAndEducationFiltersSeeder::class,
            PropertyAndAutoFiltersSeeder::class,
            MarketplaceGeneralFilterSeeder::class,
            RealEstateFilterSeeder::class,
            AutoFilterSeeder::class,
            BeautyFilterSeeder::class,
            ElectronicsFilterSeeder::class,
        ]);
    }
}


