<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\DTOs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SportsNutritionProductDto extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            public int $store_id,
            public int $category_id,
            public string $name,
            public string $sku,
            public string $brand,
            public int $price_b2c, // in kopecks
            public int $price_b2b, // in kopecks
            public int $stock_quantity,
            public string $form_factor, // powder, caps
            public int $servings_count,
            public array $nutrition_facts, // [protein => 25, carbs => 2]
            public array $allergens, // ['milk', 'soy']
            public string $expiry_date, // 'YYYY-MM-DD'
            public bool $is_vegan = false,
            public bool $is_gmo_free = true,
            public bool $is_published = false,
            public array $tags = []
        ) {}

        public static function fromArray(array $data): self
        {
            return new self(
                store_id: (int) $data['store_id'],
                category_id: (int) $data['category_id'],
                name: (string) $data['name'],
                sku: (string) $data['sku'],
                brand: (string) $data['brand'],
                price_b2c: (int) $data['price_b2c'],
                price_b2b: (int) $data['price_b2b'],
                stock_quantity: (int) ($data['stock_quantity'] ?? 0),
                form_factor: (string) $data['form_factor'],
                servings_count: (int) ($data['servings_count'] ?? 1),
                nutrition_facts: (array) ($data['nutrition_facts'] ?? []),
                allergens: (array) ($data['allergens'] ?? []),
                expiry_date: (string) $data['expiry_date'],
                is_vegan: (bool) ($data['is_vegan'] ?? false),
                is_gmo_free: (bool) ($data['is_gmo_free'] ?? true),
                is_published: (bool) ($data['is_published'] ?? false),
                tags: (array) ($data['tags'] ?? [])
            );
        }
    }

    /**
     * AISupplementRequestDto (Layer 2/9)
     * Input for the AI Constructor to analyze user needs.
     */
    final readonly class AISupplementRequestDto
    {
        public function __construct(
            public int $user_id,
            public string $goal, // 'bulking', 'cutting', 'recovery', 'endurance'
            public float $weight_kg,
            public int $age,
            public string $dietary_restriction, // 'vegan', 'keto', 'no-dairy'
            public array $active_training_days, // ['Mon', 'Wed', 'Fri']
            public int $budget_kopecks_max = 1000000
        ) {}
    }

    /**
     * AISupplementResultDto (Layer 2/9)
     * Orchestrated result from the AI Layer.
     */
    final readonly class AISupplementResultDto
    {
        public function __construct(
            public string $vertical,
            public string $recommended_stack_name,
            public array $payload, // [calories => 3000, protein => 200]
            public \Illuminate\Support\Collection $suggestions, // Collection of SportsNutritionProduct models
            public float $confidence_score,
            public string $correlation_id
        ) {}
    }

    /**
     * SubscriptionBoxDto (Layer 2/9)
     */
    final readonly class SubscriptionBoxDto
    {
        public function __construct(
            public string $name,
            public string $description,
            public int $price_monthly,
            public array $included_skus,
            public string $training_goal,
            public bool $is_active = true
        ) {}
}
