<?php

namespace Database\Seeders;

use App\Models\Domains\Beauty\Salon;
use Illuminate\Database\Seeder;

class SalonSeeder extends Seeder
{
    public function run(): void
    {
        $salons = [
            ['name' => 'Glamour Salon', 'address' => '123 Main St', 'status' => 'active'],
            ['name' => 'Beauty Lab', 'address' => '456 Oak Ave', 'status' => 'active'],
            ['name' => 'Premium Hair Studio', 'address' => '789 Pine Rd', 'status' => 'inactive'],
        ];

        foreach ($salons as $salon) {
            Salon::factory()->create($salon);
        }
    }
}
