<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Support\Str;

/**
 * SportsNutritionReview Model (Layer 1/9).
 */
final class SportsNutritionReview extends Model
{
    use SportsNutritionDomainTrait, TenantScoped;

    protected $table = 'sports_nutrition_reviews';
    protected $fillable = ['uuid', 'tenant_id', 'user_id', 'product_id', 'rating', 'comment', 'impact_data', 'is_verified_purchase', 'correlation_id'];
    protected $casts = ['impact_data' => 'json', 'is_verified_purchase' => 'boolean', 'rating' => 'integer'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(SportsNutritionProduct::class, 'product_id');
    }
}
