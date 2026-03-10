<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Central Admin User (no tenant_id for platform admins)
        User::updateOrCreate(
            ['email' => 'admin@hotelbeauty.crm'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'tenant_id' => null,
            ]
        );

        // Create Tenant 1 (Hotel)
        if (!Tenant::find('grand-hotel')) {
            $tenant1 = Tenant::create([
                'id' => 'grand-hotel',
                'name' => 'Grand Hotel Luxury',
                'type' => 'hotel',
                'plan' => 'premium',
            ]);
            $tenant1->domains()->create(['domain' => 'hotel.localhost']);
        }

        // Create Tenant 2 (Beauty Salon)
        if (!Tenant::find('spa-beauty')) {
            $tenant2 = Tenant::create([
                'id' => 'spa-beauty',
                'name' => 'Elite Spa & Beauty',
                'type' => 'beauty',
                'plan' => 'premium',
            ]);
            $tenant2->domains()->create(['domain' => 'beauty.localhost']);
        }

        $this->command->info('Tenants created: hotel.localhost, beauty.localhost');

        $this->call([
            // BaseFilterSeeder::class,  // TODO: Fix filter table structure
            TaxiRideSeeder::class,
            FoodOrderSeeder::class,
            HotelBookingSeeder::class,
            SportsMembershipSeeder::class,
            MedicalCardSeeder::class,
            DeliveryOrderSeeder::class,
            InventoryItemSeeder::class,
            AdCampaignSeeder::class,
            GeoZoneSeeder::class,
            CourseSeeder::class,
            EventSeeder::class,
            SalonSeeder::class,
            PropertySeeder::class,
            InsurancePolicySeeder::class,
            MessageSeeder::class,
        ]);
    }
}
