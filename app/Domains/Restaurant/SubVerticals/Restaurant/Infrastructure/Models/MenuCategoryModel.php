<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Media\Domain\Traits\HasMediaTrait;

final class MenuCategoryModel extends Model
{
    use HasFactory;
    use HasMediaTrait;

    protected $table = 'menu_categories';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'image_url',
        'display_order',
        'is_active',
        'parent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItemModel::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    protected static function newFactory()
    {
        return \Modules\Restaurant\Database\Factories\MenuCategoryFactory::new();
    }
}
