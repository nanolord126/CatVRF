<?php
declare(strict_types=1);

use Tests\BaseTestCase;
use Tests\SecurityTestCase;

/*
|--------------------------------------------------------------------------
| Pest Configuration
|--------------------------------------------------------------------------
|
| Pest is PHP testing framework with a focus on developers experience.
| https://pestphp.com
|
*/

// Use BaseTestCase for all tests
uses(BaseTestCase::class)->in('tests/Unit', 'tests/Feature', 'tests/Integration');
uses(SecurityTestCase::class)->in('tests/Security', 'tests/Feature/Fraud');

// Automatically detect test files
// pest()->in('tests/Unit');
// pest()->in('tests/Feature');
// pest()->in('tests/Security');
// pest()->in('tests/E2E');

// Database transactions for feature tests
// uses(DatabaseTransactions::class)->in('tests/Feature');

// Parallel execution
// --parallel --processes=8

// Custom helper functions for tests
function createTestTenant()
{
    return \App\Models\Tenant::factory()->create();
}

function createTestUser($tenant = null)
{
    return \App\Models\User::factory()->create([
        'tenant_id' => $tenant?->id ?? createTestTenant()->id,
    ]);
}

function createTestWallet($user = null)
{
    $user = $user ?? createTestUser();
    return \App\Models\Wallet::factory()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
    ]);
}

function createTestPayment($user = null, $amount = 100000)
{
    $user = $user ?? createTestUser();
    return \App\Models\PaymentTransaction::factory()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'amount' => $amount,
    ]);
}
