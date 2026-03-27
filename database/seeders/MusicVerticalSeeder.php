<?php

declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Music\Seeding;

use App\Models\Music\MusicStore;
use App\Models\Music\MusicInstrument;
use App\Models\Music\MusicStudio;
use App\Models\Music\MusicLesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * MusicVerticalSeeder provides realistic demo data for the Music vertical.
 * Note: Should NOT be run in production.
 */
class MusicVerticalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = 1; // Default tenant for seeding
        $correlationId = (string) Str::uuid();

        // 1. Create a flagship Music Store
        $store = MusicStore::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => 'Gibson & Fender Haven',
            'address' => 'Улица Музыкантов, 42, Москва',
            'geo_point' => '{"lat": 55.7558, "lon": 37.6173}',
            'schedule_json' => [
                'mon_fri' => '10:00-21:00',
                'sat_sun' => '11:00-19:00'
            ],
            'rating' => 4.9,
            'review_count' => 120,
            'is_verified' => true,
            'correlation_id' => $correlationId,
        ]);

        // 2. Create some high-end instruments
        $instruments = [
            [
                'name' => 'Fender Stratocaster 1964 Custom Shop',
                'type' => 'guitar',
                'sku' => 'GTR-FND-64-CS',
                'price' => 45000000,
                'current_stock' => 2,
                'min_stock_threshold' => 1,
            ],
            [
                'name' => 'Gibson Les Paul Standard 50s Heritage',
                'type' => 'guitar',
                'sku' => 'GTR-GBS-50S-HB',
                'price' => 28000000,
                'current_stock' => 5,
                'min_stock_threshold' => 2,
            ],
            [
                'name' => 'Yamaha C3 Studio Grand Piano',
                'type' => 'piano',
                'sku' => 'PNO-YMH-C3-STD',
                'price' => 250000000,
                'current_stock' => 1,
                'min_stock_threshold' => 1,
            ]
        ];

        foreach ($instruments as $data) {
            MusicInstrument::create(array_merge($data, [
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'store_id' => $store->id,
                'brand' => explode(' ', $data['name'])[0],
                'model' => $data['name'],
                'specifications_json' => ['condition' => 'new', 'warranty' => '2 years'],
                'is_active' => true,
                'correlation_id' => $correlationId,
            ]));
        }

        // 3. Create a Rehearsal Studio
        $studio = MusicStudio::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => 'Rockstar Rehearsal Room A',
            'description' => 'Fully equipped room for full bands. 40sqm with Marshalls and Pearl drums.',
            'hourly_rate' => 150000, // 1500 RUB
            'equipment_json' => ['Drums' => 'Pearl Masters', 'Amps' => 'Marshall JCM800'],
            'is_active' => true,
            'correlation_id' => $correlationId,
        ]);

        // 4. Create a Private Guitar Lesson
        MusicLesson::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'instructor_name' => 'Джимми Хендрикс (Мл.)',
            'instrument_type' => 'guitar',
            'duration_minutes' => 60,
            'price' => 350000, // 3500 RUB
            'is_online' => false,
            'description' => 'Уроки блюз-рока и импровизации.',
            'is_active' => true,
            'correlation_id' => $correlationId,
        ]);
    }
}
