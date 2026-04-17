<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Support\Str;

/**
 * SportsNutritionConsumable Model (Layer 1/9).
 */
final class SportsNutritionConsumable extends Model
{
    use SportsNutritionDomainTrait, TenantScoped;

    protected $table = 'sports_nutrition_consumables';
    protected $fillable = ['uuid', 'tenant_id', 'name', 'stock_kg', 'min_threshold', 'purity_percentage', 'correlation_id'];
    protected $casts = ['stock_kg' => 'float', 'min_threshold' => 'float'];
}
