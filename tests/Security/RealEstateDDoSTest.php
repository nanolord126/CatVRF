<?php declare(strict_types=1);

namespace Tests\Security;

use App\Domains\RealEstate\Models\Property;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class RealEstateDDoSTest extends SecurityTestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 75.5,
            'price' => 10000000.00,
        ]);
    }

    public function test_property_search_ddos_blocked(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 100; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->getJson('/api/real-estate/properties/search?q=test' . $i);
        }

        $rateLimitedCount = collect($responses)->filter(fn($r) => $r->status() === 429)->count();
        $this->assertGreaterThan(20, $rateLimitedCount, 'Search DDoS should trigger rate limiting');
    }

    public function test_property_detail_ddos_blocked(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 50; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->getJson("/api/real-estate/properties/{$this->property->id}");
        }

        $rateLimitedCount = collect($responses)->filter(fn($r) => $r->status() === 429)->count();
        $this->assertGreaterThan(10, $rateLimitedCount, 'Property detail DDoS should trigger rate limiting');
    }

    public function test_scoring_endpoint_ddos_blocked(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 30; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->postJson('/api/real-estate/scoring', [
                    'property_id' => $this->property->id,
                    'deal_amount' => 10000000.00,
                    'is_b2b' => false,
                ]);
        }

        $rateLimitedCount = collect($responses)->filter(fn($r) => $r->status() === 429)->count();
        $this->assertGreaterThan(5, $rateLimitedCount, 'Scoring DDoS should trigger rate limiting');
    }

    public function test_api_endpoint_flooding_blocked(): void
    {
        $endpoints = [
            '/api/real-estate/properties',
            '/api/real-estate/agents',
            '/api/real-estate/transactions',
            '/api/real-estate/viewings',
        ];

        $totalRateLimited = 0;
        
        foreach ($endpoints as $endpoint) {
            $responses = [];
            for ($i = 0; $i < 25; $i++) {
                $responses[] = $this->actingAs($this->user)
                    ->getJson($endpoint);
            }
            $totalRateLimited += collect($responses)->filter(fn($r) => $r->status() === 429)->count();
        }

        $this->assertGreaterThan(15, $totalRateLimited, 'API flooding should trigger rate limiting across endpoints');
    }

    public function test_concurrent_requests_ddos_mitigated(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 20; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->getJson("/api/real-estate/properties/{$this->property->id}");
        }

        $successfulCount = collect($responses)->filter(fn($r) => $r->status() === 200)->count();
        $this->assertLessThan(15, $successfulCount, 'Concurrent requests should be throttled');
    }

    public function test_ip_based_rate_limiting(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 40; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->withHeader('X-Forwarded-For', '192.168.1.100')
                ->getJson('/api/real-estate/properties');
        }

        $rateLimitedCount = collect($responses)->filter(fn($r) => $r->status() === 429)->count();
        $this->assertGreaterThan(10, $rateLimitedCount, 'IP-based rate limiting should work');
    }

    public function test_user_based_rate_limiting(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 35; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->postJson('/api/real-estate/inquiries', [
                    'property_id' => $this->property->id,
                    'message' => 'Test message',
                ]);
        }

        $rateLimitedCount = collect($responses)->filter(fn($r) => $r->status() === 429)->count();
        $this->assertGreaterThan(8, $rateLimitedCount, 'User-based rate limiting should work');
    }

    public function test_tenant_level_ddos_protection(): void
    {
        $users = [];
        for ($i = 0; $i < 10; $i++) {
            $users[] = User::factory()->create(['tenant_id' => $this->tenant->id]);
        }

        $responses = [];
        foreach ($users as $user) {
            for ($i = 0; $i < 10; $i++) {
                $responses[] = $this->actingAs($user)
                    ->getJson('/api/real-estate/properties');
            }
        }

        $rateLimitedCount = collect($responses)->filter(fn($r) => $r->status() === 429)->count();
        $this->assertGreaterThan(20, $rateLimitedCount, 'Tenant-level DDoS protection should activate');
    }

    public function test_slowloris_attack_prevention(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 15; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->withHeader('Connection', 'keep-alive')
                ->getJson('/api/real-estate/properties');
        }

        $rateLimitedCount = collect($responses)->filter(fn($r) => $r->status() === 429)->count();
        $this->assertGreaterThan(3, $rateLimitedCount, 'Slowloris-like connections should be rate-limited');
    }

    public function test_burst_request_limiting(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 50; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->getJson('/api/real-estate/properties');
        }

        $burstCount = 0;
        $consecutiveSuccess = 0;
        
        foreach ($responses as $response) {
            if ($response->status() === 200) {
                $consecutiveSuccess++;
                if ($consecutiveSuccess > 20) {
                    $burstCount++;
                }
            } else {
                $consecutiveSuccess = 0;
            }
        }

        $this->assertEquals(0, $burstCount, 'Burst requests should be limited');
    }

    public function test_ddos_retry_after_header(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 30; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->getJson('/api/real-estate/properties');
        }

        $rateLimitedResponses = collect($responses)->filter(fn($r) => $r->status() === 429);
        
        foreach ($rateLimitedResponses as $response) {
            $this->assertNotNull($response->headers->get('Retry-After'), 'Rate limited response should include Retry-After header');
        }
    }

    public function test_distributed_ddos_detection(): void
    {
        $tenants = [];
        $usersPerTenant = [];
        
        for ($i = 0; $i < 5; $i++) {
            $tenants[] = Tenant::factory()->create();
            $usersPerTenant[$i] = [];
            
            for ($j = 0; $j < 5; $j++) {
                $usersPerTenant[$i][] = User::factory()->create(['tenant_id' => $tenants[$i]->id]);
            }
        }

        $responses = [];
        foreach ($usersPerTenant as $tenantUsers) {
            foreach ($tenantUsers as $user) {
                for ($k = 0; $k < 5; $k++) {
                    $responses[] = $this->actingAs($user)
                        ->getJson('/api/real-estate/properties');
                }
            }
        }

        $rateLimitedCount = collect($responses)->filter(fn($r) => $r->status() === 429)->count();
        $this->assertGreaterThan(25, $rateLimitedCount, 'Distributed DDoS should be detected and mitigated');
    }
}
