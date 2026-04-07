<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * CosmeticProduct — косметический товар, продающийся в салоне.
 * Цена хранится в копейках (integer).
 *
 * @property int    $id
 * @property string $uuid
 * @property int    $tenant_id
 * @property int    $salon_id
 * @property string $brand
 * @property string $name
 * @property string $category
 * @property int    $volume_ml
 * @property int    $price               (копейки)
 * @property int    $stock
 * @property bool   $is_available
 * @property bool   $is_professional     (только для мастеров)
 * @property string $correlation_id
 */
final class CosmeticProduct extends Model
{
    protected $table = 'cosmetic_products';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'salon_id',
        'brand',
        'name',
        'description',
        'category',
        'volume_ml',
        'price',
        'stock',
        'is_available',
        'is_professional',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'tags'            => 'json',
        'is_available'    => 'boolean',
        'is_professional' => 'boolean',
        'price'           => 'integer',
        'stock'           => 'integer',
        'volume_ml'       => 'integer',
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

    public function isInStock(): bool
    {
        return $this->is_available && $this->stock > 0;
    }

    public function isProfessionalOnly(): bool
    {
        return $this->is_professional;
    }

    public function decrementStock(int $qty = 1): void
    {
        if ($this->stock - $qty < 0) {
            throw new \RuntimeException("Недостаточно товара '{$this->name}' на складе.");
        }
        $this->decrement('stock', $qty);
    }

    public function getPriceRubles(): float
    {
        return $this->price / 100;
    }
}