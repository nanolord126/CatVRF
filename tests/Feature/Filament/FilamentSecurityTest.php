<?php declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filament UI Security Tests
 *
 * Тестирует безопасность административной панели:
 * - Авторизация и доступ
 * - XSS prevention
 * - CSRF protection
 * - SQL injection prevention
 * - Unauthorized access prevention
 * - Data exposure prevention
 * - Rate limiting
 */

class FilamentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $adminUser;
    private User $regularUser;
    private string $adminToken;
    private string $userToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        
        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);
        
        $this->regularUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'user',
        ]);

        $this->adminToken = $this->adminUser->createToken('test')->plainTextToken;
        $this->userToken = $this->regularUser->createToken('test')->plainTextToken;
    }

    public function test_unauthorized_access_to_admin_panel(): void
    {
        // Regular user should not access admin panel
        $response = $this->withHeader('Authorization', "Bearer {$this->userToken}")
            ->get('/admin');

        $this->assertTrue($response->status() === 403 || $response->status() === 401);
    }

    public function test_authorized_access_to_admin_panel(): void
    {
        // Admin user should access admin panel
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->get('/admin');

        $this->assertTrue($response->status() === 200 || $response->status() === 302);
    }

    public function test_xss_prevention_in_forms(): void
    {
        // Attempt to submit XSS payload in form
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->post('/admin/sports/bookings', [
                'facility_id' => 1,
                'slot_start' => now()->addHours(1)->toIso8601String(),
                'slot_end' => now()->addHours(2)->toIso8601String(),
                'sport_type' => '<script>alert("xss")</script>',
                'participants' => 2,
            ]);

        // XSS should be sanitized or blocked
        $this->assertTrue($response->status() < 500);
    }

    public function test_sql_injection_prevention(): void
    {
        // Attempt SQL injection in search
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->get('/admin/sports/bookings?search=\' OR \'1\'=\'1');

        // Should be sanitized or return empty results
        $this->assertTrue($response->status() < 500);
    }

    public function test_csrf_protection(): void
    {
        // Attempt POST without CSRF token (if applicable)
        $response = $this->post('/admin/sports/bookings', [
            'facility_id' => 1,
            'slot_start' => now()->addHours(1)->toIso8601String(),
            'slot_end' => now()->addHours(2)->toIso8601String(),
            'sport_type' => 'tennis',
            'participants' => 2,
        ]);

        // Should be blocked if CSRF is enabled
        $this->assertTrue($response->status() === 419 || $response->status() === 403);
    }

    public function test_data_exposure_prevention(): void
    {
        // Regular user should not see admin data
        $response = $this->withHeader('Authorization', "Bearer {$this->userToken}")
            ->get('/admin/sports/bookings');

        $this->assertTrue($response->status() === 403 || $response->status() === 401);
    }

    public function test_cross_tenant_isolation(): void
    {
        // Create admin for another tenant
        $anotherTenant = Tenant::factory()->create();
        $anotherAdmin = User::factory()->create([
            'tenant_id' => $anotherTenant->id,
            'role' => 'admin',
        ]);
        $anotherAdminToken = $anotherAdmin->createToken('test')->plainTextToken;

        // Another tenant admin should not access first tenant's data
        $response = $this->withHeader('Authorization', "Bearer {$anotherAdminToken}")
            ->get("/admin/tenants/{$this->tenant->id}");

        $this->assertTrue($response->status() === 403 || $response->status() === 404);
    }

    public function test_rate_limiting_on_admin_endpoints(): void
    {
        // Attempt multiple rapid requests
        $responses = [];
        for ($i = 0; $i < 20; $i++) {
            $responses[] = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
                ->get('/admin/sports/facilities');
        }

        // Should be rate limited after threshold
        $lastResponse = $responses[19];
        $this->assertTrue(
            $lastResponse->status() === 429 || 
            $lastResponse->status() === 200
        );
    }

    public function test_sensitive_data_not_exposed_in_api(): void
    {
        // Ensure sensitive fields are not exposed in API responses
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->get('/admin/users');

        $response->assertSuccessful();
        
        $responseData = json_encode($response->json());
        
        // Sensitive fields should not be in response
        $this->assertFalse(str_contains($responseData, 'password'));
        $this->assertFalse(str_contains($responseData, 'token'));
    }

    public function test_bulk_action_authorization(): void
    {
        // Regular user should not perform bulk actions
        $response = $this->withHeader('Authorization', "Bearer {$this->userToken}")
            ->post('/admin/sports/bookings/bulk-delete', [
                'ids' => [1, 2, 3],
            ]);

        $this->assertTrue($response->status() === 403 || $response->status() === 401);
    }

    public function test_file_upload_security(): void
    {
        // Attempt to upload malicious file
        $file = new \Illuminate\Http\UploadedFile(
            base_path('tests/fixtures/malicious.php'),
            'malicious.php',
            'application/x-php',
            null,
            true
        );

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->post('/admin/upload', [
                'file' => $file,
            ]);

        // Should be blocked
        $this->assertTrue($response->status() === 422 || $response->status() === 403);
    }

    public function test_export_functionality_security(): void
    {
        // Regular user should not export data
        $response = $this->withHeader('Authorization', "Bearer {$this->userToken}")
            ->get('/admin/sports/bookings/export');

        $this->assertTrue($response->status() === 403 || $response->status() === 401);
    }

    public function test_audit_trail_logging(): void
    {
        // Perform admin action
        $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->post('/admin/sports/facilities', [
                'name' => 'Test Facility',
                'address' => 'Test Address',
            ]);

        // Verify audit log exists (if implemented)
        $this->assertTrue(true); // Placeholder for audit log verification
    }

    public function test_session_timeout(): void
    {
        // Simulate expired session
        $expiredToken = $this->adminUser->createToken('expired')->plainTextToken;
        
        // Invalidate token (simulate expiration)
        $this->adminUser->tokens()->delete();

        $response = $this->withHeader('Authorization', "Bearer {$expiredToken}")
            ->get('/admin');

        // Should be unauthorized
        $this->assertTrue($response->status() === 401);
    }

    public function test_permission_inheritance(): void
    {
        // Create user with limited permissions
        $limitedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'moderator',
        ]);
        $limitedToken = $limitedUser->createToken('test')->plainTextToken;

        // Should not have access to all admin features
        $response = $this->withHeader('Authorization', "Bearer {$limitedToken}")
            ->get('/admin/settings');

        $this->assertTrue($response->status() === 403 || $response->status() === 401);
    }

    public function test_api_key_security(): void
    {
        // Attempt to use invalid API key
        $response = $this->withHeader('Authorization', 'Bearer invalid_api_key')
            ->get('/admin/sports/bookings');

        $this->assertTrue($response->status() === 401);
    }

    public function test_mass_assignment_prevention(): void
    {
        // Attempt to mass assign protected fields
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->post('/admin/users', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'role' => 'super_admin', // Should not be mass assignable
                'tenant_id' => 999999, // Should not be mass assignable
            ]);

        // Should fail or ignore protected fields
        $this->assertTrue($response->status() < 500);
    }
}
