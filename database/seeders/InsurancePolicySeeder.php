<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Domains\Insurance\InsurancePolicy;
use Illuminate\Database\Seeder;

/**
 * Тестовые страховые полисы (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class InsurancePolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            ["number" => "POL-001", "type" => "health", "premium_amount" => 200],
            ["number" => "POL-002", "type" => "auto", "premium_amount" => 150],
            ["number" => "POL-003", "type" => "home", "premium_amount" => 250],
        ];

        foreach ($policies as $policy) {
            InsurancePolicy::factory()->create($policy);
        }
    }
}

