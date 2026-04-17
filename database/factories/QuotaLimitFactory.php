<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\QuotaLimit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Quota Limit Factory
 * 
 * Production 2026 CANON - Test Data Generation
 */
class QuotaLimitFactory extends Factory
{
    protected $model = QuotaLimit::class;

    public function definition(): array
    {
        $resourceTypes = ['ai_tokens', 'llm_requests', 'slot_holds', 'geo_queries', 'payment_attempts'];
        $periods = ['hourly', 'daily', 'monthly'];
        $planTypes = ['free', 'starter', 'pro', 'enterprise'];

        return [
            'tenant_id' => $this->faker->optional()->numberBetween(1, 1000),
            'business_group_id' => $this->faker->optional()->numberBetween(1, 100),
            'resource_type' => $this->faker->randomElement($resourceTypes),
            'vertical_code' => $this->faker->optional()->randomElement(['medical', 'beauty', 'auto', 'fitness', 'sports']),
            'period' => $this->faker->randomElement($periods),
            'limit' => $this->faker->numberBetween(1000, 10000000),
            'soft_limit' => function (array $attributes) {
                return (int) ($attributes['limit'] * 0.85); // 85% of limit
            },
            'is_hard_limit' => $this->faker->boolean(80), // 80% chance of hard limit
            'plan_type' => $this->faker->randomElement($planTypes),
            'metadata' => $this->faker->optional()->randomElement([
                ['description' => 'Custom limit for enterprise client'],
                ['reason' => 'Trial period extended'],
                ['approved_by' => 'admin'],
            ]),
        ];
    }

    /**
     * State for free plan limits.
     */
    public function free(): self
    {
        return $this->state(fn (array $attributes) => [
            'plan_type' => 'free',
            'limit' => $this->faker->numberBetween(100, 1000),
        ]);
    }

    /**
     * State for starter plan limits.
     */
    public function starter(): self
    {
        return $this->state(fn (array $attributes) => [
            'plan_type' => 'starter',
            'limit' => $this->faker->numberBetween(1000, 10000),
        ]);
    }

    /**
     * State for pro plan limits.
     */
    public function pro(): self
    {
        return $this->state(fn (array $attributes) => [
            'plan_type' => 'pro',
            'limit' => $this->faker->numberBetween(10000, 100000),
        ]);
    }

    /**
     * State for enterprise plan limits.
     */
    public function enterprise(): self
    {
        return $this->state(fn (array $attributes) => [
            'plan_type' => 'enterprise',
            'limit' => $this->faker->numberBetween(100000, 10000000),
            'is_hard_limit' => false, // Enterprise often has soft limits
        ]);
    }

    /**
     * State for AI tokens resource.
     */
    public function aiTokens(): self
    {
        return $this->state(fn (array $attributes) => [
            'resource_type' => 'ai_tokens',
            'limit' => $this->faker->numberBetween(10000, 10000000),
        ]);
    }

    /**
     * State for LLM requests resource.
     */
    public function llmRequests(): self
    {
        return $this->state(fn (array $attributes) => [
            'resource_type' => 'llm_requests',
            'limit' => $this->faker->numberBetween(100, 10000),
        ]);
    }

    /**
     * State for slot holds resource.
     */
    public function slotHolds(): self
    {
        return $this->state(fn (array $attributes) => [
            'resource_type' => 'slot_holds',
            'limit' => $this->faker->numberBetween(50, 5000),
        ]);
    }

    /**
     * State for hourly period.
     */
    public function hourly(): self
    {
        return $this->state(fn (array $attributes) => [
            'period' => 'hourly',
        ]);
    }

    /**
     * State for daily period.
     */
    public function daily(): self
    {
        return $this->state(fn (array $attributes) => [
            'period' => 'daily',
            'limit' => $attributes['limit'] * 24, // 24x hourly
        ]);
    }

    /**
     * State for monthly period.
     */
    public function monthly(): self
    {
        return $this->state(fn (array $attributes) => [
            'period' => 'monthly',
            'limit' => $attributes['limit'] * 720, // 30 days * 24 hours
        ]);
    }

    /**
     * State for tenant-specific limit.
     */
    public function forTenant(int $tenantId): self
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
            'business_group_id' => null,
        ]);
    }

    /**
     * State for business group limit.
     */
    public function forBusinessGroup(int $businessGroupId): self
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
            'business_group_id' => $businessGroupId,
        ]);
    }

    /**
     * State for default limit (no tenant or group).
     */
    public function default(): self
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
            'business_group_id' => null,
        ]);
    }
}
