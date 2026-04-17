<?php declare(strict_types=1);

namespace Tests\Unit\Services\Tenancy;

use App\Http\Middleware\TenantMiddleware;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Hash;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

/**
 * Tenant Middleware Test
 *
 * Production 2026 CANON - Multi-Tenant Security Tests
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class TenantMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private TenantMiddleware $middleware;
    private DatabaseManager $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = app(DatabaseManager::class);
        $logger = app(LoggerInterface::class);

        $this->middleware = new TenantMiddleware(
            $this->db,
            $logger
        );
    }

    public function test_user_based_identification_with_valid_tenant(): void
    {
        // Create tenant
        $tenant = Tenant::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Test Tenant',
            'inn' => '1234567890',
            'is_active' => true,
        ]);

        // Create user with tenant
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
        ]);

        // Create request with authenticated user
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        // Handle request
        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($tenant->id, $request->attributes->get('tenant_id'));
    }

    public function test_user_based_identification_fails_without_user(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('User not authenticated');

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => null);

        $this->middleware->handle($request, fn ($req) => response('OK'));
    }

    public function test_user_based_identification_fails_without_tenant(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('User has no tenant assigned');

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => null,
        ]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->middleware->handle($request, fn ($req) => response('OK'));
    }

    public function test_user_based_identification_fails_with_inactive_tenant(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Tenant not found or inactive');

        $tenant = Tenant::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Inactive Tenant',
            'inn' => '0987654321',
            'is_active' => false,
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
        ]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->middleware->handle($request, fn ($req) => response('OK'));
    }

    public function test_header_based_identification_with_valid_signature(): void
    {
        config(['tenancy.identification.resolvers.header' => true]);

        $tenant = Tenant::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Test Tenant',
            'inn' => '1234567890',
            'is_active' => true,
            'meta' => ['api_secret' => 'test_secret'],
        ]);

        $timestamp = time();
        $signature = hash_hmac('sha256', $tenant->id . $timestamp, 'test_secret');

        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Tenant-ID', $tenant->id);
        $request->headers->set('X-Tenant-Signature', $signature);
        $request->headers->set('X-Tenant-Timestamp', (string) $timestamp);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($tenant->id, $request->attributes->get('tenant_id'));
    }

    public function test_header_based_identification_fails_with_missing_headers(): void
    {
        config(['tenancy.identification.resolvers.header' => true]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Missing required tenant identification headers');

        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Tenant-ID', 'test-id');

        $this->middleware->handle($request, fn ($req) => response('OK'));
    }

    public function test_header_based_identification_fails_with_expired_timestamp(): void
    {
        config(['tenancy.identification.resolvers.header' => true]);

        $tenant = Tenant::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Test Tenant',
            'inn' => '1234567890',
            'is_active' => true,
            'meta' => ['api_secret' => 'test_secret'],
        ]);

        $timestamp = time() - 400; // 400 seconds ago (exceeds 300 second tolerance)
        $signature = hash_hmac('sha256', $tenant->id . $timestamp, 'test_secret');

        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Tenant-ID', $tenant->id);
        $request->headers->set('X-Tenant-Signature', $signature);
        $request->headers->set('X-Tenant-Timestamp', (string) $timestamp);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Tenant signature timestamp expired');

        $this->middleware->handle($request, fn ($req) => response('OK'));
    }

    public function test_header_based_identification_fails_with_invalid_signature(): void
    {
        config(['tenancy.identification.resolvers.header' => true]);

        $tenant = Tenant::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Test Tenant',
            'inn' => '1234567890',
            'is_active' => true,
            'meta' => ['api_secret' => 'test_secret'],
        ]);

        $timestamp = time();
        $invalidSignature = 'invalid_signature';

        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Tenant-ID', $tenant->id);
        $request->headers->set('X-Tenant-Signature', $invalidSignature);
        $request->headers->set('X-Tenant-Timestamp', (string) $timestamp);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid tenant signature');

        $this->middleware->handle($request, fn ($req) => response('OK'));
    }

    protected function tearDown(): void
    {
        config(['tenancy.identification.resolvers.header' => false]);
        parent::tearDown();
    }
}
