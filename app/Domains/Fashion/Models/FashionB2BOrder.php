<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * КАНЬОН 2026 — ОПТОВЫЙ ЗАКАЗ B2B
 * 
 * Содержит ИНН покупателя и состав заказа в JSONB.
 */
final class FashionB2BOrder extends Model
{
    protected $table = 'fashion_b2b_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'fashion_store_id',
        'buyer_inn',
        'total_amount',
        'status',
        'items_json',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'items_json' => 'json',
        'metadata' => 'json',
        'total_amount' => 'integer',
        'tenant_id' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(FashionStore::class, 'fashion_store_id');
    }
}
