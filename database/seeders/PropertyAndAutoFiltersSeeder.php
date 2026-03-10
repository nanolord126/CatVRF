<?php

namespace Database\Seeders;

use App\Models\Filter;
use App\Models\FilterValue;
use Illuminate\Database\Seeder;

class PropertyAndAutoFiltersSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAuto();
        $this->seedConstruction();
        $this->seedRealEstate();
        $this->seedShoes();
    }

    private function seedAuto()
    {
        $f = Filter::create(['vertical' => 'AutoService', 'name' => 'EV Service Center', 'type' => 'boolean']);
        $f = Filter::create(['vertical' => 'AutoService', 'name' => 'Body Work', 'type' => 'boolean']);
    }

    private function seedConstruction()
    {
        Filter::create(['vertical' => 'Construction', 'name' => 'Eco-Certified Wood', 'type' => 'boolean']);
        Filter::create(['vertical' => 'Construction', 'name' => 'Recycled Content (%)', 'type' => 'range', 'unit' => '%']);
    }

    private function seedRealEstate()
    {
        Filter::create(['vertical' => 'RealEstate', 'name' => 'Smart Home Standard', 'type' => 'boolean']);
        Filter::create(['vertical' => 'RealEstate', 'name' => 'Area (m2)', 'type' => 'range', 'unit' => 'm2']);
    }

    private function seedShoes()
    {
        $f = Filter::create(['vertical' => 'Shoes', 'name' => 'Gender', 'type' => 'select']);
        foreach (['Unisex', 'Female', 'Male', 'Kids'] as $v) FilterValue::create(['filter_id' => $f->id, 'value' => $v, 'label' => $v]);
        Filter::create(['vertical' => 'Shoes', 'name' => 'Anti-Microbial Sole', 'type' => 'boolean']);
    }
}
// Shoes seeder end


