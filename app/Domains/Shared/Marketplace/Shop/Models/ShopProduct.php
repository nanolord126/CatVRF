<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Shop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class ShopProduct extends Model
{
    protected $table = 'shop_products';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'sku',
        'category',
        'price_kopeks',
        'compare_at_price_kopeks',
        'attributes',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'attributes' => 'json',
        'tags' => 'json',
    ];

    /**
     * Выполнить операцию
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price_kopeks / 100, 2, '.', ' ').' ₽';
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
