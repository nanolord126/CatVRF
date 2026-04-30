<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class B2BPriceRule extends Model
{
    protected $table = 'b2b_price_rules';

    protected $fillable = [
        'product_id',
        'tenant_id',
        'min_quantity',
        'price_per_unit',
        'discount_percent',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'min_quantity' => 'integer',
        'price_per_unit' => 'integer',
        'discount_percent' => 'integer',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    public function getEffectivePrice(int $retailPrice): int
    {
        if ($this->price_per_unit > 0) {
            return $this->price_per_unit;
        }

        // No discount - use retail price
        return $retailPrice;
    }

    public function getCashbackAmount(int $price): int
    {
        $cashbackPercent = $this->cashback_percent ?? 0;
        return (int) ($price * $cashbackPercent / 100);
    }
}
