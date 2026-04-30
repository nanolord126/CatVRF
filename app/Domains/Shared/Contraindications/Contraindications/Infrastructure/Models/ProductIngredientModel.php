<?php

declare(strict_types=1);

namespace Modules\Contraindications\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProductIngredientModel extends Model
{
    use HasFactory;

    protected $table = 'product_ingredients';

    protected $fillable = [
        'name',
        'is_allergen',
        'common_allergy_name',
        'description',
    ];

    protected $casts = [
        'is_allergen' => 'boolean',
    ];

    public function scopeAllergens($query)
    {
        return $query->where('is_allergen', true);
    }
}
