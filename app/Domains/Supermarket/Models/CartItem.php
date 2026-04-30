<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CartItem extends Model
{
    protected $table = 'cart_items';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'product_id',
        'quantity',
        'attributes',
        'reservation_expires_at',
    ];

    protected $casts = [
        'attributes' => 'array',
        'reservation_expires_at' => 'datetime',
        'quantity' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('reservation_expires_at', '>', now());
    }

    public function scopeForUser($query, int $userId, ?string $tenantId)
    {
        return $query->where('user_id', $userId)
            ->where('tenant_id', $tenantId);
    }
}
