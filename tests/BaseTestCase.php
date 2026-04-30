<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Illuminate\Testing\TestResponse;
use Filament\Facades\Filament;
use Illuminate\Support\Str;

abstract class BaseTestCase extends LaravelTestCase
{
    use DatabaseTransactions;
    use WithFaker;

    protected ?Tenant $tenant = null;

    protected ?User $user = null;

    protected string $correlationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->correlationId = Str::uuid()->toString();
        $this->configureTestEnvironment();
        // Skip clearCache in test environment to avoid Mockery issues
        // Cache is already set to 'array' in configureTestEnvironment()
        $this->clearRedis();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant '.$this->correlationId,
            'slug' => 'test-'.$this->correlationId,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'test-'.$this->correlationId.'@example.com',
        ]);

        if (class_exists('\Filament\Facades\Filament')) {
            try {
                Filament::setTenant($this->tenant);
            } catch (\Throwable $e) {
                // No Filament panel active in unit tests — ignore
            }
        }
    }

    protected function tearDown(): void
    {
        $this->clearRedis();
        parent::tearDown();
    }

    protected function configureTestEnvironment(): void
    {
        Config::set('app.env', 'testing');
        Config::set('cache.default', 'array');
        Config::set('queue.default', 'sync');
        Config::set('session.driver', 'array');
    }

    protected function clearCache(): void
    {
        try {
            Artisan::call('cache:clear');
        } catch (\Throwable $e) {
            // Ignore console errors in test environment
        }
        try {
            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            // Ignore console errors in test environment
        }
        try {
            Artisan::call('route:clear');
        } catch (\Throwable $e) {
            // Ignore console errors in test environment
        }
        try {
            Artisan::call('view:clear');
        } catch (\Throwable $e) {
            // Ignore console errors in test environment
        }
    }

    
    protected function clearRedis(): void
    {
        if (Config::get('cache.default') === 'redis') {
            Redis::flushdb();
        }
    }

    protected function actingAsTenant(int $tenantId): self
    {
        tenant()->initialize($tenantId);
        return $this;
    }

    protected function authenticatedGet(
        string $uri,
        array $headers = [],
        ?User $user = null
    ): TestResponse {
        return $this->actingAs($user ?? $this->user)
            ->withHeaders([
                'X-Correlation-ID' => $this->correlationId,
                'Accept' => 'application/json',
                ...$headers,
            ])
            ->get($uri);
    }

    protected function authenticatedPost(
        string $uri,
        array $data = [],
        array $headers = [],
        ?User $user = null
    ): TestResponse {
        return $this->actingAs($user ?? $this->user)
            ->withHeaders([
                'X-Correlation-ID' => $this->correlationId,
                'Accept' => 'application/json',
                'X-Fraud-Check' => 'enabled',
                ...$headers,
            ])
            ->post($uri, $data);
    }

    protected function authenticatedPut(
        string $uri,
        array $data = [],
        array $headers = [],
        ?User $user = null
    ): TestResponse {
        return $this->actingAs($user ?? $this->user)
            ->withHeaders([
                'X-Correlation-ID' => $this->correlationId,
                'Accept' => 'application/json',
                ...$headers,
            ])
            ->put($uri, $data);
    }

    protected function authenticatedDelete(
        string $uri,
        array $headers = [],
        ?User $user = null
    ): TestResponse {
        return $this->actingAs($user ?? $this->user)
            ->withHeaders([
                'X-Correlation-ID' => $this->correlationId,
                'Accept' => 'application/json',
                ...$headers,
            ])
            ->delete($uri);
    }

    protected function assertHasCorrelationId(TestResponse $response): void
    {
        $response->assertHeader('X-Correlation-ID');
        $this->assertNotEmpty($response->headers->get('X-Correlation-ID'));
    }

    protected function assertHasFraudScore(TestResponse $response): void
    {
        $data = $response->json();
        $this->assertArrayHasKey('fraud_score', $data);
        $this->assertGreaterThanOrEqual(0, $data['fraud_score']);
        $this->assertLessThanOrEqual(1, $data['fraud_score']);
    }

    protected function assertTenantScoped(TestResponse $response): void
    {
        $data = $response->json();
        $this->assertArrayHasKey('tenant_id', $data);
        $this->assertEquals($this->tenant->id, $data['tenant_id']);
    }

    protected function assertRateLimitHeaders(TestResponse $response, int $limit = 100): void
    {
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
        $response->assertHeader('X-RateLimit-Reset');

        $limitHeader = (int) $response->headers->get('X-RateLimit-Limit');
        $this->assertLessThanOrEqual($limit, $limitHeader);
    }

    protected function assertRateLimitResponse(TestResponse $response): void
    {
        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    }

    protected function assertNoSideEffects(): void
    {
        $this->assertDatabaseMissing('users', ['email' => 'test-side-effect@test.com']);
    }

    protected function logTestInfo(string $message, array $context = []): void
    {
        \Log::channel('test')->info($message, [
            'correlation_id' => $this->correlationId,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            ...$context,
        ]);
    }

    protected function createSecondUser(): User
    {
        return User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    protected function createSecondTenant(): Tenant
    {
        return Tenant::factory()->create([
            'name' => 'Second Tenant '.$this->correlationId,
        ]);
    }
}
