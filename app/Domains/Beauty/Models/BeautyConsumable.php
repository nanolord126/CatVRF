<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * BeautyConsumable — расходник салона (краска, перчатки, полотенца и т.д.).
 * current_stock — количество в натуральных единицах (шт / мл / г).
 * unit_cost — цена единицы в копейках.
 *
 * @property int    $id
 * @property string $uuid
 * @property int    $tenant_id
 * @property int    $salon_id
 * @property string $name
 * @property string $unit              (шт / мл / г / л)
 * @property int    $current_stock
 * @property int    $min_threshold
 * @property int    $unit_cost         (копейки)
 * @property string $correlation_id
 */
final class BeautyConsumable extends Model
{
    protected $table = 'beauty_consumables';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'salon_id',
        'name',
        'unit',
        'current_stock',
        'min_threshold',
        'unit_cost',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'current_stock' => 'integer',
        'min_threshold' => 'integer',
        'unit_cost'     => 'integer',
        'tags'          => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::addGlobalScope('tenant', static function ($query): void {
            if ($tenantId = tenant()->id) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(BeautySalon::class, 'salon_id');
    }

    public function isBelowThreshold(): bool
    {
        return $this->current_stock <= $this->min_threshold;
    }

    public function isOutOfStock(): bool
    {
        return $this->current_stock <= 0;
    }

    public function decrementStock(int $quantity): void
    {
        $this->decrement('current_stock', $quantity);
    }

    public function incrementStock(int $quantity): void
    {
        $this->increment('current_stock', $quantity);
    }

    public function getTotalCostKopecks(): int
    {
        return $this->current_stock * $this->unit_cost;
    }
}