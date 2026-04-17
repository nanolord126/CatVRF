<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Sports;

use App\Domains\Sports\Models\Gym;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DynamicPricingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Gym $gym;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->gym = Gym::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'name' => 'Test Gym',
            'address' => 'Test Address',
            'single_visit_price' => 500,
            'monthly_membership_price' => 3000,
            'personal_training_price' => 1500,
            'group_class_price' => 500,
            'max_daily_capacity' => 200,
            'is_active' => true,
        ]);
    }

    public function test_calculate_dynamic_price(): void
    {
        $response = $this->getJson("/api/v1/sports/pricing/venues/{$this->gym->id}/calculate", [
            'service_type' => 'single_visit',
            'is_b2b' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'base_price',
                'final_price',
                'load_factor',
                'time_multiplier',
                'is_flash_discount',
                'is_b2b',
            ]);
    }

    public function test_calculate_dynamic_price_b2b(): void
    {
        $response = $this->getJson("/api/v1/sports/pricing/venues/{$this->gym->id}/calculate", [
            'service_type' => 'single_visit',
            'is_b2b' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'is_b2b' => true,
            ]);
    }

    public function test_get_bulk_pricing(): void
    {
        $response = $this->getJson("/api/v1/sports/pricing/venues/{$this->gym->id}/bulk-pricing", [
            'employee_count' => 50,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'employee_count',
                'individual_price',
                'bulk_discount_percentage',
                'bulk_price_per_employee',
                'total_price',
                'savings',
            ]);
    }

    public function test_create_flash_membership_validation_error(): void
    {
        $response = $this->postJson("/api/v1/sports/pricing/venues/{$this->gym->id}/flash-membership", [
            'membership_type' => 'invalid',
            'duration_days' => 400,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['membership_type', 'duration_days']);
    }

    public function test_show_membership_not_found(): void
    {
        $response = $this->getJson('/api/v1/sports/pricing/memberships/999');

        $response->assertStatus(404);
    }
}
