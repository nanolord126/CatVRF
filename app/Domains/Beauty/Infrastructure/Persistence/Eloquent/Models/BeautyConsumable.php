<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent-модель расходника (материала, списываемого при оказании услуги).
 *
 * InventoryManagementService управляет остатками только через методы
 * reserveStock / releaseStock / deductStock / addStock.
 * Прямое изменение current_stock / hold_stock запрещено.
 *
 * @property int         $id
 * @property string      $uuid
 * @property int         $tenant_id
 * @property int|null    $business_group_id
 * @property int         $service_id
 * @property string      $name
 * @property string      $unit
 * @property int         $current_stock    Фактический остаток
 * @property int         $hold_stock       Зарезервированный остаток
 * @property int         $min_stock_threshold Минимальный порог (уведомление)
 * @property float       $quantity_per_service Расход на одну услугу
 * @property array|null  $tags
 * @property string|null $correlation_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class BeautyConsumable extends Model
{
    protected $table = 'beauty_consumables';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'service_id',
        'name',
        'unit',
        'current_stock',
        'hold_stock',
        'min_stock_threshold',
        'quantity_per_service',
        'tags',
        'correlation_id',
    ];

    protected $hidden = [];

    protected $casts = [
        'current_stock'        => 'integer',
        'hold_stock'           => 'integer',
        'min_stock_threshold'  => 'integer',
        'quantity_per_service' => 'float',
        'tags'                 => 'json',
    ];

    /**
     * Глобальный scope по tenant_id.
     * Запрещено выполнять запросы без tenant scoping.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('beauty_consumables.tenant_id', tenant()->id);
            }
        });
    }

    // ===== Отношения =====

    public function service(): BelongsTo
    {
        return $this->belongsTo(BeautyService::class, 'service_id');
    }

    // ===== Scope-запросы =====

    /**
     * Расходники с остатком ниже минимального порога (нужна закупка).
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereRaw('current_stock - hold_stock <= min_stock_threshold');
    }

    /**
     * Доступный остаток (без учёта hold).
     */
    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->current_stock - $this->hold_stock);
    }
}
