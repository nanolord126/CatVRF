<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Domains\CRM\Models\CrmClient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Фабрика CrmClient — основная модель CRM.
 * Канон CatVRF 2026.
 */
final class CrmClientFactory extends Factory
{
    protected $model = CrmClient::class;

    private const VERTICALS = [
        'beauty', 'hotel', 'flowers', 'auto', 'food', 'furniture',
        'fashion', 'fitness', 'real_estate', 'medical', 'education',
        'travel', 'pet', 'taxi', 'electronics', 'events',
    ];

    private const STATUSES = ['active', 'inactive', 'vip', 'blocked'];
    private const SOURCES = ['website', 'phone', 'referral', 'social', 'manual', 'import'];
    private const CLIENT_TYPES = ['individual', 'company', 'freelancer'];
    private const TIERS = ['bronze', 'silver', 'gold', 'platinum'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'business_group_id' => null,
            'user_id' => null,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'tags' => $this->faker->randomElements(['vip', 'loyal', 'new', 'corporate', 'promo'], 2),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'company_name' => $this->faker->optional(0.3)->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '+7' . $this->faker->numerify('9#########'),
            'phone_secondary' => $this->faker->optional(0.2)->numerify('+79#########'),
            'client_type' => $this->faker->randomElement(self::CLIENT_TYPES),
            'status' => $this->faker->randomElement(self::STATUSES),
            'source' => $this->faker->randomElement(self::SOURCES),
            'vertical' => $this->faker->randomElement(self::VERTICALS),
            'addresses' => [
                ['city' => $this->faker->city(), 'street' => $this->faker->streetAddress()],
            ],
            'total_spent' => $this->faker->randomFloat(2, 0, 500000),
            'total_orders' => $this->faker->numberBetween(0, 200),
            'average_order_value' => $this->faker->randomFloat(2, 500, 25000),
            'bonus_points' => $this->faker->numberBetween(0, 50000),
            'loyalty_tier' => $this->faker->randomElement(self::TIERS),
            'segment' => $this->faker->optional(0.5)->word(),
            'last_interaction_at' => $this->faker->optional(0.8)->dateTimeBetween('-90 days', 'now'),
            'last_order_at' => $this->faker->optional(0.6)->dateTimeBetween('-180 days', 'now'),
            'preferences' => ['notifications' => true, 'language' => 'ru'],
            'special_notes' => [],
            'internal_notes' => $this->faker->optional(0.3)->sentence(),
            'vertical_data' => [],
            'avatar_url' => null,
            'preferred_language' => 'ru',
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function vip(): static
    {
        return $this->state(fn () => [
            'status' => 'vip',
            'loyalty_tier' => 'platinum',
            'total_spent' => $this->faker->randomFloat(2, 100000, 1000000),
        ]);
    }

    public function sleeping(int $days = 60): static
    {
        return $this->state(fn () => [
            'last_interaction_at' => now()->subDays($days),
        ]);
    }

    public function forVertical(string $vertical): static
    {
        return $this->state(fn () => ['vertical' => $vertical]);
    }
}
