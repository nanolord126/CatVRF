<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Photography\Models\PhotographySession;
use App\Domains\Photography\Models\PhotographyGallery;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PhotographySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Photography vertical...');

            for ($i = 1; $i <= 20; $i++) {
                PhotographySession::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'title' => "Session {$i}",
                    'description' => "Description for session {$i}",
                    'type' => ['portrait', 'wedding', 'event'][rand(0, 2)],
                    'photographer_id' => rand(1, 5),
                    'client_id' => rand(1, 10),
                    'scheduled_at' => now()->addDays(rand(1, 30)),
                    'duration_hours' => rand(1, 8),
                    'price' => rand(5000, 100000),
                    'status' => 'scheduled',
                ]);

                PhotographyGallery::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'session_id' => $i,
                    'title' => "Gallery {$i}",
                    'description' => "Description for gallery {$i}",
                    'cover_image' => "https://example.com/photos/{$i}.jpg",
                    'status' => 'published',
                ]);
            }

            $this->command->info('Photography vertical seeded successfully.');
        });
    }
}
