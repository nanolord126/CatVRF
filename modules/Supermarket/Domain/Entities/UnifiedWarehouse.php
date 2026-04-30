<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant;

/**
 * UnifiedWarehouse — Единый склад для B2B и B2C
 * 
 * Поддерживает переключение между режимами B2B и B2C
 * с единым пулом инвентаря и раздельным резервированием.
 */
final class UnifiedWarehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'name',
        'code',
        'type',
        'address',
        'city',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'manager_id',
        'is_active',
        'is_main',
        'capacity',
        'capacity_used',
        'b2b_capacity',
        'b2b_capacity_used',
        'b2c_capacity',
        'b2c_capacity_used',
        'operating_hours',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'is_main' => 'boolean',
        'capacity' => 'integer',
        'capacity_used' => 'integer',
        'b2b_capacity' => 'integer',
        'b2b_capacity_used' => 'integer',
        'b2c_capacity' => 'integer',
        'b2c_capacity_used' => 'integer',
        'operating_hours' => 'json',
        'metadata' => 'json',
    ];

    protected $table = 'unified_warehouses';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'manager_id');
    }

    public function stock(): HasMany
    {
        return $this->hasMany(UnifiedStock::class, 'warehouse_id');
    }

    public function b2bReservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class, 'warehouse_id')
            ->where('order_type', 'b2b');
    }

    public function b2cReservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class, 'warehouse_id')
            ->where('order_type', 'b2c');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeHasB2BCapacity($query)
    {
        return $query->whereColumn('b2b_capacity_used', '<', 'b2b_capacity');
    }

    public function scopeHasB2CCapacity($query)
    {
        return $query->whereColumn('b2c_capacity_used', '<', 'b2c_capacity');
    }

    // ========================
    // METHODS
    // ========================

    public function getAvailableCapacity(string $orderType): int
    {
        return $orderType === 'b2b'
            ? max(0, $this->b2b_capacity - $this->b2b_capacity_used)
            : max(0, $this->b2c_capacity - $this->b2c_capacity_used);
    }

    public function getTotalStockValue(): float
    {
        return $this->stock()->sum(\DB::raw('quantity * unit_price'));
    }

    public function getStockBySKU(string $sku): ?UnifiedStock
    {
        return $this->stock()->where('sku', $sku)->first();
    }

    public function reserveStock(string $orderType, array $items): bool
    {
        return \DB::transaction(function () use ($orderType, $items) {
            foreach ($items as $item) {
                $stock = $this->stock()->where('sku', $item['sku'])->lockForUpdate()->first();
                
                if (!$stock || $stock->quantity < $item['quantity']) {
                    throw new \RuntimeException("Insufficient stock for SKU: {$item['sku']}");
                }

                $stock->decrement('quantity', $item['quantity']);
                $stock->increment('reserved_quantity', $item['quantity']);

                if ($orderType === 'b2b') {
                    $this->increment('b2b_capacity_used', $item['quantity']);
                } else {
                    $this->increment('b2c_capacity_used', $item['quantity']);
                }
            }

            return true;
        });
    }

    public function releaseReservation(string $orderType, array $items): bool
    {
        return \DB::transaction(function () use ($orderType, $items) {
            foreach ($items as $item) {
                $stock = $this->stock()->where('sku', $item['sku'])->lockForUpdate()->first();
                
                if ($stock) {
                    $stock->decrement('reserved_quantity', $item['quantity']);
                    $stock->increment('quantity', $item['quantity']);
                }

                if ($orderType === 'b2b') {
                    $this->decrement('b2b_capacity_used', $item['quantity']);
                } else {
                    $this->decrement('b2c_capacity_used', $item['quantity']);
                }
            }

            return true;
        });
    }

    public function switchCapacity(string $fromType, string $toType, int $amount): bool
    {
        if (!in_array($fromType, ['b2b', 'b2c']) || !in_array($toType, ['b2b', 'b2c'])) {
            throw new \InvalidArgumentException('Invalid order type');
        }

        if ($fromType === $toType) {
            return true;
        }

        $fromCapacity = $fromType === 'b2b' ? $this->b2b_capacity : $this->b2c_capacity;
        $fromUsed = $fromType === 'b2b' ? $this->b2b_capacity_used : $this->b2c_capacity_used;

        if ($fromUsed + $amount > $fromCapacity) {
            throw new \RuntimeException('Insufficient capacity to switch');
        }

        return \DB::transaction(function () use ($fromType, $toType, $amount) {
            if ($fromType === 'b2b') {
                $this->decrement('b2b_capacity', $amount);
                $this->increment('b2c_capacity', $amount);
            } else {
                $this->decrement('b2c_capacity', $amount);
                $this->increment('b2b_capacity', $amount);
            }

            return true;
        });
    }

    protected static function booted(): void
    {
        parent::booted();
        static::creating(fn ($m) => $m->uuid ??= \Str::uuid());
    }
}
