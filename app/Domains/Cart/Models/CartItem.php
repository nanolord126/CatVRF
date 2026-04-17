<?php declare(strict_types=1);

namespace App\Domains\Cart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'price_at_add',
        'current_price',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'price_at_add' => 'integer',
        'current_price' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->whereHas('cart', fn($q) => $q->where('tenant_id', tenant()->id));
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Effective price per canon: never pay less than what was added
     */
    public function getEffectivePrice(): int
    {
        return max($this->price_at_add, $this->current_price);
    }

    public function getTotal(): int
    {
        return $this->getEffectivePrice() * $this->quantity;
    }
}
