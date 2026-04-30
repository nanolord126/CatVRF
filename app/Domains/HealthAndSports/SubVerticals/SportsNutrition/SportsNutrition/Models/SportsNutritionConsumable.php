<?php

declare(strict_types=1);

namespace App\Domains\HealthAndSports\SubVerticals\SportsNutrition\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

/**
 * SportsNutritionConsumable Model (Layer 1/9).
 */
final class SportsNutritionConsumable extends Model
{
    use SportsNutritionDomainTrait;
    use TenantScoped;

    protected $table = 'sports_nutrition_consumables';

    protected $fillable = ['uuid', 'tenant_id', 'name', 'stock_kg', 'min_threshold', 'purity_percentage', 'correlation_id'];

    protected $casts = ['stock_kg' => 'float', 'min_threshold' => 'float'];
}
