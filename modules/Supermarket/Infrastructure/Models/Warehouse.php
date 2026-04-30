<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Warehouse extends Model
{
    use SoftDeletes;

    protected $table = 'supermarket_warehouses';

    protected $fillable = [
        'uuid',
        'owner_id',
        'type',
        'name',
        'address',
        'city',
        'region',
        'postal_code',
        'latitude',
        'longitude',
        'area',
        'capacity',
        'has_cold_storage',
        'has_freezer',
        'storage_zones',
        'status',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'area' => 'decimal:2',
        'capacity' => 'decimal:3',
        'has_cold_storage' => 'boolean',
        'has_freezer' => 'boolean',
        'storage_zones' => 'array',
        'metadata' => 'array',
    ];

    public const TYPE_B2B = 'b2b';
    public const TYPE_B2C = 'b2c';
    public const TYPE_MIXED = 'mixed';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_MAINTENANCE = 'maintenance';

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function isB2B(): bool
    {
        return $this->type === self::TYPE_B2B || $this->type === self::TYPE_MIXED;
    }

    public function isB2C(): bool
    {
        return $this->type === self::TYPE_B2C || $this->type === self::TYPE_MIXED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
