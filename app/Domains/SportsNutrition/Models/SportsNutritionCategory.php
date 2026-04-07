<?php declare(strict_types=1);

namespace App\Domains\SportsNutrition\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * SportsNutritionCategory Model (Layer 1/9).
 */
final class SportsNutritionCategory extends Model
{
    use SportsNutritionDomainTrait;

    protected $table = 'sports_nutrition_categories';
    protected $fillable = ['uuid', 'tenant_id', 'name', 'slug', 'description', 'is_active'];

    public function products(): HasMany
    {
        return $this->hasMany(SportsNutritionProduct::class, 'category_id');
    }
}
