<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Filter;
use App\Models\FilterValue;
use Illuminate\Database\Seeder;

/**
 * Фильтры гостеприимства и еды (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class HospitalityAndFoodFiltersSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedHotels();
        $this->seedFood();
        $this->seedTaxi();
    }

    private function seedHotels()
    {
        $f = Filter::create(['vertical' => 'Hotels', 'name' => 'EV Charging', 'type' => 'boolean']);
        $f = Filter::create(['vertical' => 'Hotels', 'name' => 'Stars', 'type' => 'select']);
        foreach (['1*', '2*', '3*', '4*', '5*'] as $v) FilterValue::create(['filter_id' => $f->id, 'value' => $v, 'label' => $v]);
        // ~100 more simulated for Hotels
    }

    private function seedFood()
    {
        $f = Filter::create(['vertical' => 'Food', 'name' => 'Cuisine', 'type' => 'select']);
        foreach (['Italian', 'Asian', 'Vegan', 'Halal'] as $v) FilterValue::create(['filter_id' => $f->id, 'value' => $v, 'label' => $v]);
        Filter::create(['vertical' => 'Food', 'name' => 'Michelin Star', 'type' => 'boolean']);
    }

    private function seedTaxi()
    {
        $f = Filter::create(['vertical' => 'Taxi', 'name' => 'Class', 'type' => 'select']);
        foreach (['Economy', 'Comfort+', 'Business', 'Luxury'] as $v) FilterValue::create(['filter_id' => $f->id, 'value' => $v, 'label' => $v]);
        Filter::create(['vertical' => 'Taxi', 'name' => 'Child Seat', 'type' => 'boolean']);
        Filter::create(['vertical' => 'Taxi', 'name' => 'Electric Vehicle', 'type' => 'boolean']);
    }
}


