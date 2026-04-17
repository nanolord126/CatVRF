<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Models\PropertyPhoto;
use App\Domains\RealEstate\Models\PropertyDocument;
use App\Domains\RealEstate\Models\Viewing;
use App\Domains\RealEstate\Models\Contract;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RealEstateSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Real Estate vertical...');

            $agentId = DB::table('users')->where('role', 'agent')->value('id') ?? 1;

            for ($i = 1; $i <= 20; $i++) {
                $property = Property::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'agent_id' => $agentId,
                    'title' => "Property {$i}",
                    'description' => "Description for property {$i}",
                    'property_type' => ['apartment', 'house', 'commercial'][rand(0, 2)],
                    'price' => rand(100000, 10000000),
                    'area' => rand(50, 500),
                    'rooms' => rand(1, 10),
                    'address' => "Address {$i}, City",
                    'latitude' => rand(55000000, 56000000) / 1000000,
                    'longitude' => rand(37000000, 38000000) / 1000000,
                    'status' => ['available', 'rented', 'sold'][rand(0, 2)],
                    'features' => json_encode(['parking', 'elevator', 'balcony']),
                ]);

                PropertyPhoto::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'property_id' => $property->id,
                    'url' => "https://example.com/property/{$i}.jpg",
                    'is_primary' => $i === 1,
                ]);

                PropertyDocument::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'property_id' => $property->id,
                    'name' => "Document {$i}",
                    'url' => "https://example.com/docs/property/{$i}.pdf",
                    'type' => 'contract',
                ]);

                Viewing::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'property_id' => $property->id,
                    'client_id' => rand(1, 10),
                    'scheduled_at' => now()->addDays(rand(1, 30)),
                    'status' => 'scheduled',
                ]);

                Contract::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'property_id' => $property->id,
                    'client_id' => rand(1, 10),
                    'type' => ['rent', 'sale'][rand(0, 1)],
                    'start_date' => now()->addDays(rand(1, 30)),
                    'end_date' => now()->addDays(rand(31, 365)),
                    'rent_amount' => rand(10000, 100000),
                    'status' => 'active',
                ]);
            }

            $this->command->info('Real Estate vertical seeded successfully.');
        });
    }
}
