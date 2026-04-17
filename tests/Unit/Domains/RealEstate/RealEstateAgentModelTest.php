<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\RealEstate;

use Tests\TestCase;
use App\Models\Domains\RealEstate\RealEstateAgent;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class RealEstateAgentModelTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
    }

    public function test_agent_has_fillable_fields(): void
    {
        $agent = RealEstateAgent::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'full_name' => 'Test Agent',
            'license_number' => 'LICENSE123',
            'phone' => '+79001234567',
            'email' => 'agent@example.com',
            'rating' => 4.5,
            'deals_count' => 10,
            'is_active' => true,
            'correlation_id' => \Illuminate\Support\Str::uuid(),
        ]);

        $this->assertDatabaseHas('real_estate_agents', [
            'id' => $agent->id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'full_name' => 'Test Agent',
            'license_number' => 'LICENSE123',
        ]);
    }

    public function test_scope_active_filters_only_active_agents(): void
    {
        RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);
        RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);
        RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => false,
        ]);

        $activeAgents = RealEstateAgent::active()->get();

        $this->assertCount(2, $activeAgents);
        $activeAgents->each(fn ($agent) => $this->assertTrue($agent->is_active));
    }

    public function test_scope_top_rated_filters_rating_above_threshold(): void
    {
        RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rating' => 4.8,
        ]);
        RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rating' => 4.6,
        ]);
        RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rating' => 4.3,
        ]);
        RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rating' => 3.9,
        ]);

        $topRatedAgents = RealEstateAgent::topRated()->get();

        $this->assertCount(3, $topRatedAgents);
        $topRatedAgents->each(fn ($agent) => $this->assertGreaterThanOrEqual(4.5, $agent->rating));
    }

    public function test_scope_experienced_filters_deals_count_above_threshold(): void
    {
        RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'deals_count' => 25,
        ]);
        RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'deals_count' => 15,
        ]);
        RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'deals_count' => 8,
        ]);
        RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'deals_count' => 3,
        ]);

        $experiencedAgents = RealEstateAgent::experienced()->get();

        $this->assertCount(3, $experiencedAgents);
        $experiencedAgents->each(fn ($agent) => $this->assertGreaterThanOrEqual(10, $agent->deals_count));
    }

    public function test_uuid_is_generated_on_creation(): void
    {
        $agent = RealEstateAgent::factory()->create([
            'uuid' => null,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertNotNull($agent->uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $agent->uuid);
    }

    public function test_correlation_id_is_generated_on_creation(): void
    {
        $agent = RealEstateAgent::factory()->create([
            'correlation_id' => null,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertNotNull($agent->correlation_id);
    }

    public function test_rating_cast_works_correctly(): void
    {
        $agent = RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rating' => 4.75,
        ]);

        $this->assertEquals(4.8, $agent->rating);
        $this->assertIsFloat($agent->rating);
    }

    public function test_deals_count_cast_works_correctly(): void
    {
        $agent = RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'deals_count' => 42,
        ]);

        $this->assertEquals(42, $agent->deals_count);
        $this->assertIsInt($agent->deals_count);
    }

    public function test_is_active_cast_works_correctly(): void
    {
        $agent = RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => 1,
        ]);

        $this->assertTrue($agent->is_active);
        $this->assertIsBool($agent->is_active);
    }

    public function test_tags_cast_works_correctly(): void
    {
        $agent = RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'tags' => ['luxury', 'commercial', 'residential'],
        ]);

        $this->assertIsArray($agent->tags);
        $this->assertEquals(['luxury', 'commercial', 'residential'], $agent->tags);
    }

    public function test_user_relationship(): void
    {
        $agent = RealEstateAgent::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertInstanceOf(User::class, $agent->user);
        $this->assertEquals($this->user->id, $agent->user->id);
    }

    public function test_tenant_relationship(): void
    {
        $agent = RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $agent->tenant);
        $this->assertEquals($this->tenant->id, $agent->tenant->id);
    }

    public function test_license_number_is_unique(): void
    {
        $licenseNumber = 'UNIQUE123';

        RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'license_number' => $licenseNumber,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        RealEstateAgent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'license_number' => $licenseNumber,
        ]);
    }
}
