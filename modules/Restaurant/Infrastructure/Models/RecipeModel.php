<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RecipeModel extends Model
{
    use HasFactory;

    protected $table = 'recipes';

    protected $fillable = [
        'tenant_id',
        'menu_item_id',
        'ingredient_id',
        'quantity',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItemModel::class, 'menu_item_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(IngredientModel::class, 'ingredient_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
