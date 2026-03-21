<?php
declare(strict_types=1);

namespace Tests\Feature\Api\Controllers;

use App\Domains\HealthyFood\Models\DietPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

final class HealthyFoodDietControllerTest extends TestCase
{
    use DatabaseTransactions;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_list_diet_plans_returns_200(): void
    {
        DietPlan::factory(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson('/api/v1/diet-plans');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.diet_type', fn($type) => in_array($type, ['keto', 'vegan', 'paleo', 'balanced']));
    }

    public function test_create_diet_plan_with_valid_data_returns_201(): void
    {
        $data = [
            'diet_type' => 'keto',
            'duration_days' => 30,
            'daily_calories' => 1800,
            'description' => 'Кетогенная диета 30 дней',
        ];

        $response = $this->postJson('/api/v1/diet-plans', $data);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.diet_type', 'keto');

        $this->assertDatabaseHas('diet_plans', ['diet_type' => 'keto']);
    }

    public function test_subscribe_to_diet_plan_returns_200(): void
    {
        $plan = DietPlan::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->postJson("/api/v1/diet-plans/{$plan->id}/subscribe");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_create_diet_plan_validates_duration_days(): void
    {
        $data = [
            'diet_type' => 'vegan',
            'duration_days' => 0, // Минимум 7
            'daily_calories' => 1800,
        ];

        $response = $this->postJson('/api/v1/diet-plans', $data);

        $response->assertStatus(422);
    }

    public function test_create_diet_plan_validates_calories(): void
    {
        $data = [
            'diet_type' => 'balanced',
            'duration_days' => 14,
            'daily_calories' => 800, // Меньше 1000
        ];

        $response = $this->postJson('/api/v1/diet-plans', $data);

        $response->assertStatus(422);
    }

    public function test_diet_plan_correlation_id_in_response(): void
    {
        $response = $this->getJson('/api/v1/diet-plans');

        $this->assertTrue(Str::isUuid($response->json('correlation_id')));
    }

    public function test_diet_plan_tenant_scoping(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherPlan = DietPlan::factory()->create(['tenant_id' => $otherTenant->id]);
        $myPlan = DietPlan::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson('/api/v1/diet-plans');

        $planIds = collect($response->json('data'))->pluck('id');
        $this->assertContains($myPlan->id, $planIds);
        $this->assertNotContains($otherPlan->id, $planIds);
    }
}
