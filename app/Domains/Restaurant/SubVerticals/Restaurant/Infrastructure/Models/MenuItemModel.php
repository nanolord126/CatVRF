<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Media\Domain\Traits\HasMediaTrait;
use Modules\Video\Domain\Traits\HasVideoTrait;

final class MenuItemModel extends Model
{
    use HasFactory;
    use HasMediaTrait;
    use HasVideoTrait;

    protected $table = 'menu_items';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'name',
        'description',
        'price_kopecks',
        'image_url',
        'sku',
        'preparation_time',
        'is_active',
        'is_available',
        'is_featured',
        'allergens',
        'nutritional_info',
        'calories',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'price_kopecks' => 'integer',
        'preparation_time' => 'integer',
        'calories' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategoryModel::class, 'category_id');
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(MenuItemModifierModel::class, 'menu_item_id');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(RecipeModel::class, 'menu_item_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    protected static function newFactory()
    {
        return \Modules\Restaurant\Database\Factories\MenuItemFactory::new();
    }
}
