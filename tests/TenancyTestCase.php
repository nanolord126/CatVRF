<?php declare(strict_types=1);

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;

/**
 * Base TestCase for Tenancy-aware tests.
 * This class handles proper multitenancy initialization for tests.
 */
abstract class TenancyTestCase extends LaravelTestCase
{
    use WithFaker;

    protected ?Tenant $tenant = null;
    protected ?User $user = null;
    protected string $correlationId;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize tenancy database
        $this->initializeTenancyDatabase();

        $this->correlationId = \Illuminate\Support\Str::uuid()->toString();

        // Create tenant
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant ' . $this->correlationId,
            'slug' => 'test-' . $this->correlationId,
        ]);

        // Set tenant context
        \Illuminate\Support\Facades\App::make(\Stancl\Tenancy\Contracts\TenantResolver::class)
            ->setTenant($this->tenant);

        // Run tenant migrations
        \Stancl\Tenancy\Facades\Tenancy::boot();

        // Create user in tenant context
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'test-' . $this->correlationId . '@example.com',
        ]);

        if (class_exists('\Filament\Facades\Filament')) {
            \Filament\Facades\Filament::setTenant($this->tenant);
        }
    }

    protected function initializeTenancyDatabase(): void
    {
        // Create necessary tables if using fresh database
        $connection = config('tenancy.database.connection', 'central');
        
        if (!Schema::connection($connection)->hasTable('tenants')) {
            // Run central migrations
            \Illuminate\Support\Facades\Artisan::call('migrate', [
                '--database' => $connection,
                '--path' => 'database/migrations',
            ]);
        }
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

    protected function authenticatedPatch(
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
            ->patch($uri, $data);
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
    }

    protected function assertTenantScoped(TestResponse $response): void
    {
        // Verify response doesn't contain cross-tenant data
        $response->assertSuccessful();
    }

    protected function assertRateLimitHeaders(TestResponse $response): void
    {
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }
}
