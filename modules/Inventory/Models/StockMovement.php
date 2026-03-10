<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'type', // in, out, adjustment
        'quantity',
        'reason',
        'correlation_id',
        'user_id',
        'reference_type',
        'reference_id',
        'is_approved',
        'status',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
