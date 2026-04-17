<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Art\Models\ArtArtwork;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ArtSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Art vertical...');

            for ($i = 1; $i <= 20; $i++) {
                ArtArtwork::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'title' => "Artwork {$i}",
                    'description' => "Description for artwork {$i}",
                    'artist' => "Artist {$i}",
                    'medium' => ['oil', 'acrylic', 'watercolor'][rand(0, 2)],
                    'price' => rand(5000, 500000),
                    'status' => 'available',
                ]);
            }

            $this->command->info('Art vertical seeded successfully.');
        });
    }
}
