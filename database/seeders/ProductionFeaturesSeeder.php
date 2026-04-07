<?php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessGroup;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Str;

/**
 * Функции production среды (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class ProductionFeaturesSeeder extends Seeder {
    public function run(): void {
        $owner = User::first() ?? User::factory()->create(['email' => 'admin@catvrf.ru']);
        $group = BusinessGroup::firstOrCreate(['inn' => '7701234567'], [
            'name' => 'CatVRF 2026 Holding',
            'owner_id' => $owner->id,
            'correlation_id' => (string) Str::uuid()
        ]);
        $main = Tenant::first();
        if ($main) {
            $main->update(['business_group_id' => $group->id, 'type' => 'head']);
            Tenant::firstOrCreate(['id' => 'branch-east-2026'], [
                'name' => 'CatVRF East Branch',
                'parent_id' => $main->id, 
                'business_group_id' => $group->id, 
                'type' => 'branch'
            ]);
        }
    }
}
