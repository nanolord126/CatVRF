<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Communication\Models\CommunicationChannel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CommunicationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Communication vertical...');

            for ($i = 1; $i <= 20; $i++) {
                CommunicationChannel::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Channel {$i}",
                    'type' => ['email', 'sms', 'push'][rand(0, 2)],
                    'status' => 'active',
                ]);
            }

            $this->command->info('Communication vertical seeded successfully.');
        });
    }
}
