<?php

namespace Database\Seeders;

use App\Models\Filter;
use App\Models\FilterValue;
use Illuminate\Database\Seeder;

class RetailAndGoodsFiltersSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFlowers();
        $this->seedElectronics();
        $this->seedClothing();
        $this->seedKids();
        $this->seedHousehold();
    }

    private function seedFlowers()
    {
        $f = Filter::create(['vertical' => 'Flowers', 'name' => 'Occasion', 'type' => 'select']);
        foreach (['Wedding', 'Birthday', 'Funeral', 'Romance'] as $v) FilterValue::create(['filter_id' => $f->id, 'value' => $v, 'label' => $v]);
        Filter::create(['vertical' => 'Flowers', 'name' => 'Organic Certified', 'type' => 'boolean']);
    }

    private function seedElectronics()
    {
        Filter::create(['vertical' => 'Electronics', 'name' => 'AI Processor', 'type' => 'boolean']);
        $f = Filter::create(['vertical' => 'Electronics', 'name' => 'Screen Resolution', 'type' => 'select']);
        foreach (['4K UHD', '8K Pro', 'FHD+'] as $v) FilterValue::create(['filter_id' => $f->id, 'value' => $v, 'label' => $v]);
    }

    private function seedClothing()
    {
        $f = Filter::create(['vertical' => 'Clothing', 'name' => 'Fabric Category', 'type' => 'select']);
        foreach (['Natural Cotton', 'Bio-Polymer', 'Recycled Silk'] as $v) FilterValue::create(['filter_id' => $f->id, 'value' => $v, 'label' => $v]);
        Filter::create(['vertical' => 'Clothing', 'name' => 'Machine Washable', 'type' => 'boolean']);
    }

    private function seedKids()
    {
        $f = Filter::create(['vertical' => 'KidsToys', 'name' => 'Age Group', 'type' => 'select']);
        foreach (['0-3m', '3-6m', '1y+', '3y+'] as $v) FilterValue::create(['filter_id' => $f->id, 'value' => $v, 'label' => $v]);
        Filter::create(['vertical' => 'KidsToys', 'name' => 'Bio-degradable Plastic', 'type' => 'boolean']);
    }

    private function seedHousehold()
    {
        Filter::create(['vertical' => 'HouseholdChemicals', 'name' => 'Phosphate-Free', 'type' => 'boolean']);
        Filter::create(['vertical' => 'HouseholdChemicals', 'name' => 'Vegan Formula', 'type' => 'boolean']);
    }
}


