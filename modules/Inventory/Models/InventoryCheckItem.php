<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель элемента инвентаризационной проверки.
 * Согласно КАНОН 2026: сравнение системного остатка с физическим, выявление недостач и переизбытков.
 *
 * @property int $id
 * @property int $inventory_check_id
 * @property int $product_id
 * @property int $tenant_id
 * @property int $expected_quantity Ожидаемое количество (из системы)
 * @property int $actual_quantity Физическое количество (после проверки)
 * @property int|null $difference Разница (actual - expected)
 * @property string|null $discrepancy_type (shortage, overage, match)
 * @property bool $has_discrepancy Есть ли расхождение
 * @property string|null $notes Примечания
 * @property string|null $correlation_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class InventoryCheckItem extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_check_items';

    protected $fillable = [
        'inventory_check_id',
        'product_id',
        'tenant_id',
        'expected_quantity',
        'actual_quantity',
        'difference',
        'discrepancy_type',
        'has_discrepancy',
        'notes',
        'correlation_id',
    ];

    protected $casts = [
        'expected_quantity' => 'integer',
        'actual_quantity' => 'integer',
        'difference' => 'integer',
        'has_discrepancy' => 'boolean',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Типы расхождений.
     */
    public const DISCREPANCY_TYPE_SHORTAGE = 'shortage'; // Недостача
    public const DISCREPANCY_TYPE_OVERAGE = 'overage';   // Переизбыток
    public const DISCREPANCY_TYPE_MATCH = 'match';       // Совпадение

    /**
     * Global scope для tenant scoping.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoped', function ($query) {
            if ($tenantId = tenant('id')) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    /**
     * Получить товар.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\Models\Product::class);
    }

    /**
     * Получить проверку.
     */
    public function inventoryCheck(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\Models\InventoryCheck::class, 'inventory_check_id');
    }

    /**
     * Определить тип расхождения и обновить поля.
     */
    public function calculateDiscrepancy(): void
    {
        $this->difference = $this->actual_quantity - $this->expected_quantity;

        if ($this->difference === 0) {
            $this->discrepancy_type = self::DISCREPANCY_TYPE_MATCH;
            $this->has_discrepancy = false;
        } elseif ($this->difference > 0) {
            $this->discrepancy_type = self::DISCREPANCY_TYPE_OVERAGE;
            $this->has_discrepancy = true;
        } else {
            $this->discrepancy_type = self::DISCREPANCY_TYPE_SHORTAGE;
            $this->has_discrepancy = true;
        }

        $this->save();
    }

    /**
     * Проверить, есть ли недостача.
     */
    public function isShortage(): bool
    {
        return $this->discrepancy_type === self::DISCREPANCY_TYPE_SHORTAGE;
    }

    /**
     * Проверить, есть ли переизбыток.
     */
    public function isOverage(): bool
    {
        return $this->discrepancy_type === self::DISCREPANCY_TYPE_OVERAGE;
    }

    /**
     * Получить процент разницы.
     */
    public function getDifferencePercentage(): float
    {
        if ($this->expected_quantity <= 0) {
            return 0.0;
        }

        return abs(($this->difference / $this->expected_quantity) * 100.0);
    }
}
