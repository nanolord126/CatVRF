<?php

namespace Database\Seeders;

use App\Models\Automotive;
use Illuminate\Database\Seeder;

class AutoVerticalSeeder extends Seeder
{
    public function run(): void
    {
        $branches = ['branch-east-2026', 'grand-hotel'];
        
        foreach ($branches as $tenantId) {
            tenancy()->initialize($tenantId);
            
            Automotive::firstOrCreate(['name' => 'Tesla Service Center'], [
                'type' => 'repair',
                'inn' => '7701122334',
            ]);

            Automotive::firstOrCreate(['name' => 'Premium Car Wash 2026'], [
                'type' => 'wash',
                'inn' => '7701122334',
            ]);
            
            tenancy()->end();
        }
    }
}
