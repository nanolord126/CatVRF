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
        // Central Admin User
        User::updateOrCreate(
            ['email' => 'admin@hotelbeauty.crm'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
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
            BaseFilterSeeder::class,
        ]);

        // $this->call([

        // Run Tenant Specific Seeds for Grand Hotel
        // if ($tenant = Tenant::find('grand-hotel')) {
        //     $tenant->run(function () {
        //         $this->call(Tenant\CoreVerticalSeeder::class);
        //         $this->call(Tenant\ConsumerBehaviorSeeder::class);
        //     });
        // }
    }
}
