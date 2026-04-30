<?php

declare(strict_types=1);

namespace App\Domains\HealthAndSports\SubVerticals\SportsNutrition\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

/**
 * SportsNutritionSubscriptionBox Model (Layer 1/9).
 */
final class SportsNutritionSubscriptionBox extends Model
{
    use SportsNutritionDomainTrait;
    use TenantScoped;

    protected $table = 'sports_nutrition_subscription_boxes';

    protected $fillable = ['uuid', 'tenant_id', 'name', 'description', 'price_monthly', 'included_skus', 'training_goal', 'is_active', 'correlation_id'];

    protected $casts = ['included_skus' => 'json', 'price_monthly' => 'integer', 'is_active' => 'boolean'];

    public function getEstimatedNutritionAttribute(): array
    {
        // Composite attribute logic for UI
        return ['goal' => $this->training_goal, 'items_count' => count($this->included_skus ?? [])];
    }
}
