<?php

declare(strict_types=1);

namespace App\Models\Art;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * ArtOrder Model — Management of purchases.
 */
final class ArtOrder extends Model
{
    protected $table = 'art_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'customer_id',
        'items_json',
        'total_amount_cents',
        'status',
        'payment_status',
        'is_b2b',
        'shipping_address',
        'shipping_details',
        'correlation_id',
    ];

    protected $casts = [
        'items_json' => 'array',
        'shipping_details' => 'array',
        'is_b2b' => 'boolean',
        'total_amount_cents' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ArtOrder $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        static::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (tenant()->id ?? 1));
        });
    }

    /**
     * Customer who placed the order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'customer_id');
    }
}
