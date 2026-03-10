<?php

namespace Database\Seeders;

use App\Models\Filter;
use App\Models\FilterValue;
use Illuminate\Database\Seeder;

class ProfessionalAndEducationFiltersSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedEvents();
        $this->seedSports();
        $this->seedEducation();
    }

    private function seedEvents()
    {
        $f = Filter::create(['vertical' => 'Events', 'name' => 'Event Type', 'type' => 'select']);
        foreach (['Concert', 'Workshop', 'Festival', 'Private Party'] as $v) FilterValue::create(['filter_id' => $f->id, 'value' => $v, 'label' => $v]);
        Filter::create(['vertical' => 'Events', 'name' => 'Zero-Waste Certified', 'type' => 'boolean']);
    }

    private function seedSports()
    {
        $f = Filter::create(['vertical' => 'Sports', 'name' => 'Intensity', 'type' => 'select']);
        foreach (['Low', 'Medium', 'Hardcore', 'Pro'] as $v) FilterValue::create(['filter_id' => $f->id, 'value' => $v, 'label' => $v]);
        Filter::create(['vertical' => 'Sports', 'name' => 'Wearable Sync Ready', 'type' => 'boolean']);
    }

    private function seedEducation()
    {
        $f = Filter::create(['vertical' => 'Education', 'name' => 'Metaverse Compatible', 'type' => 'boolean']);
        $f = Filter::create(['vertical' => 'Education', 'name' => 'Course Level', 'type' => 'select']);
        foreach (['Beginner', 'Advanced', 'Masters', 'PhD Prep'] as $v) FilterValue::create(['filter_id' => $f->id, 'value' => $v, 'label' => $v]);
    }
}


