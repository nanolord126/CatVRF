<?php

declare(strict_types=1);

namespace App\Domains\HealthAndSports\SubVerticals\SportsNutrition\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

/**
 * SportsNutritionCategory Model (Layer 1/9).
 */
final class SportsNutritionCategory extends Model
{
    use SportsNutritionDomainTrait;
    use TenantScoped;

    protected $table = 'sports_nutrition_categories';

    protected $fillable = ['uuid', 'tenant_id', 'name', 'slug', 'description', 'is_active'];

    public function products(): HasMany
    {
        return $this->hasMany(SportsNutritionProduct::class, 'category_id');
    }
}
