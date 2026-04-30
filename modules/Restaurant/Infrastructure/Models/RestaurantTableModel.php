<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RestaurantTableModel extends Model
{
    use HasFactory;

    protected $table = 'restaurant_tables';

    protected $fillable = [
        'tenant_id',
        'zone_id',
        'name',
        'number',
        'capacity',
        'min_capacity',
        'shape',
        'x',
        'y',
        'width',
        'height',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'min_capacity' => 'integer',
        'x' => 'integer',
        'y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(RestaurantZoneModel::class, 'zone_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(TableReservationModel::class, 'table_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForZone($query, int $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }
}
