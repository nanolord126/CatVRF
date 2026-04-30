<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionItem extends Model
{
    protected $table = 'supermarket_subscription_items';

    protected $fillable = [
        'subscription_id',
        'product_id',
        'variant_id',
        'quantity',
        'price_per_unit_at_creation',
    ];

    protected $casts = [
        'price_per_unit_at_creation' => 'decimal:2',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function getTotalAtCreation(): float
    {
        return $this->quantity * $this->price_per_unit_at_creation;
    }
}
