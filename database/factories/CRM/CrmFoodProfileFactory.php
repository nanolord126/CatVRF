<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Domains\CRM\Models\CrmFoodProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика CrmFoodProfile — food-профиль CRM-клиента.
 * Канон CatVRF 2026.
 */
final class CrmFoodProfileFactory extends Factory
{
    protected $model = CrmFoodProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'crm_client_id' => null,
            'tenant_id' => 1,
            'correlation_id' => $this->faker->uuid(),
            'dietary_restrictions' => $this->faker->randomElements(['vegetarian', 'vegan', 'halal', 'kosher', 'gluten_free'], 1),
            'allergies' => $this->faker->randomElements(['nuts', 'gluten', 'dairy', 'shellfish', 'eggs'], 1),
            'favorite_cuisines' => $this->faker->randomElements(['Italian', 'Japanese', 'Russian', 'Chinese', 'Mexican'], 2),
            'favorite_dishes' => [],
            'disliked_ingredients' => [],
            'preferred_spiciness' => $this->faker->randomElement(['mild', 'medium', 'hot', 'extra_hot']),
            'daily_calorie_target' => $this->faker->numberBetween(1500, 3000),
            'macros_target' => ['protein' => 30, 'carbs' => 50, 'fat' => 20],
            'meal_plan_type' => $this->faker->randomElement(['weight_loss', 'muscle_gain', 'maintenance', 'medical']),
            'avg_order_frequency_days' => $this->faker->numberBetween(1, 14),
            'avg_order_amount' => $this->faker->randomFloat(2, 500, 5000),
            'delivery_time_preferences' => ['lunch' => '12:00-14:00', 'dinner' => '18:00-20:00'],
            'is_corporate_client' => $this->faker->boolean(20),
            'corporate_headcount' => $this->faker->optional(0.2)->numberBetween(5, 200),
            'corporate_schedule' => [],
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }
}
