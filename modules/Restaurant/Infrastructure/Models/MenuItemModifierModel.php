<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MenuItemModifierModel extends Model
{
    use HasFactory;

    protected $table = 'menu_item_modifiers';

    protected $fillable = [
        'tenant_id',
        'menu_item_id',
        'name',
        'description',
        'price_kopecks',
        'is_required',
        'is_multi_select',
        'max_select_count',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_multi_select' => 'boolean',
        'price_kopecks' => 'integer',
        'max_select_count' => 'integer',
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItemModel::class, 'menu_item_id');
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
