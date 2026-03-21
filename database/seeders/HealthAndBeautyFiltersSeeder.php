<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Filter;
use App\Models\FilterValue;
use Illuminate\Database\Seeder;

/**
 * Фильтры здоровья и красоты (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class HealthAndBeautyFiltersSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBeauty();
        $this->seedClinics();
        $this->seedVet();
    }

    private function seedBeauty()
    {
        $f = Filter::create(['vertical' => 'Beauty', 'name' => 'Procedure Type', 'type' => 'select']);
        foreach (['Hair', 'Nails', 'Massage', 'Spa'] as $v) FilterValue::create(['filter_id' => $f->id, 'value' => $v, 'label' => $v]);
        Filter::create(['vertical' => 'Beauty', 'name' => 'Eco-Labels', 'type' => 'boolean']);
    }

    private function seedClinics()
    {
        $f = Filter::create(['vertical' => 'HumanClinics', 'name' => 'Specialization', 'type' => 'select']);
        foreach (['Cardiology', 'Dentistry', 'Surgery'] as $v) FilterValue::create(['filter_id' => $f->id, 'value' => $v, 'label' => $v]);
        Filter::create(['vertical' => 'HumanClinics', 'name' => 'AI Diagnostics Ready', 'type' => 'boolean']);
    }

    private function seedVet()
    {
        $f = Filter::create(['vertical' => 'VetClinics', 'name' => 'Target Animal', 'type' => 'select']);
        foreach (['Cats', 'Dogs', 'Birds', 'Exotic'] as $v) FilterValue::create(['filter_id' => $f->id, 'value' => $v, 'label' => $v]);
        Filter::create(['vertical' => 'VetClinics', 'name' => 'In-Home Visit', 'type' => 'boolean']);
    }
}


