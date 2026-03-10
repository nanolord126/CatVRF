<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishlistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'wishlist_id',
        'product_id',
        'price_at_addition',
        'collected_amount',
        'is_fully_paid'
    ];

    protected $casts = [
        'price_at_addition' => 'decimal:2',
        'collected_amount' => 'decimal:2',
        'is_fully_paid' => 'boolean',
    ];

    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\modules\Inventory\Models\Product::class);
    }
}
