<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Support\Str;

/**
 * SportsNutritionProduct Model (Layer 1/9).
 */
final class SportsNutritionProduct extends Model
{
    use SoftDeletes, SportsNutritionDomainTrait, TenantScoped;

    protected $table = 'sports_nutrition_products';
    protected $fillable = [
        'uuid', 'tenant_id', 'store_id', 'category_id', 'name', 'sku', 'brand', 'description',
        'price_b2c', 'price_b2b', 'stock_quantity', 'form_factor', 'servings_count',
        'nutrition_facts', 'allergens', 'expiry_date', 'is_vegan', 'is_gmo_free',
        'is_published', 'tags', 'correlation_id'
    ];
    protected $casts = [
        'nutrition_facts' => 'json',
        'allergens' => 'json',
        'tags' => 'json',
        'expiry_date' => 'date',
        'is_vegan' => 'boolean',
        'is_gmo_free' => 'boolean',
        'is_published' => 'boolean',
        'price_b2c' => 'integer',
        'price_b2b' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(SportsNutritionStore::class, 'store_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SportsNutritionCategory::class, 'category_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SportsNutritionReview::class, 'product_id');
    }
}
