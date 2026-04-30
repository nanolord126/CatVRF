<?php

declare(strict_types=1);

namespace App\Domains\HealthAndSports\SubVerticals\SportsNutrition\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

/**
 * SportsNutritionStore Model (Layer 1/9).
 */
final class SportsNutritionStore extends Model
{
    use SoftDeletes;
    use SportsNutritionDomainTrait;
    use TenantScoped;

    protected $table = 'sports_nutrition_stores';

    protected $fillable = ['uuid', 'tenant_id', 'name', 'license_number', 'location_address', 'working_hours', 'tags', 'rating', 'correlation_id'];

    protected $casts = ['working_hours' => 'json', 'tags' => 'json', 'rating' => 'float'];

    public function products(): HasMany
    {
        return $this->hasMany(SportsNutritionProduct::class, 'store_id');
    }
}
