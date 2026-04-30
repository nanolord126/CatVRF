<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

 // used by cart()

final class CartItem extends Model
{
    protected $table = 'cart_items';

    protected $fillable = [
        'cart_id',
        'product_id',
        'uuid',
        'quantity',
        'price_at_add',
        'current_price',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags'          => 'json',
        'price_at_add'  => 'integer',
        'current_price' => 'integer',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * product_id ссылается на доменный продукт (Beauty\Service, Food\Dish и т.д.)
     * Связь domain-specific — выполняется на уровне вертикального сервиса.
     */

    /**
     * Актуальная цена по правилу канона:
     * выросла → новая цена (пользователь платит новую)
     * упала   → старая цена (пользователь никогда не платит меньше, чем добавил)
     */
    public function getEffectivePrice(): int
    {
        return max($this->price_at_add, $this->current_price);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
