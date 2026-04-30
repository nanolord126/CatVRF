<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RestaurantZoneModel extends Model
{
    use HasFactory;

    protected $table = 'restaurant_zones';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'color',
        'display_order',
        'is_active',
        'table_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'table_count' => 'integer',
    ];

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTableModel::class, 'zone_id');
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
