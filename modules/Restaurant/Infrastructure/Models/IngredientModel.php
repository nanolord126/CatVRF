<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class IngredientModel extends Model
{
    use HasFactory;

    protected $table = 'ingredients';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'sku',
        'unit',
        'current_stock',
        'min_stock',
        'max_stock',
        'cost_per_unit_kopecks',
        'is_active',
        'supplier_id',
    ];

    protected $casts = [
        'current_stock' => 'decimal:3',
        'min_stock' => 'decimal:3',
        'max_stock' => 'decimal:3',
        'cost_per_unit_kopecks' => 'integer',
        'is_active' => 'boolean',
    ];

    public function recipes(): HasMany
    {
        return $this->hasMany(RecipeModel::class, 'ingredient_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_stock', '<=', 'min_stock');
    }
}
