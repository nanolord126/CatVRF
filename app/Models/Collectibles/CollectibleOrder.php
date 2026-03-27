<?php

declare(strict_types=1);

namespace App\Models\Collectibles;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * CollectibleOrder — Record of successful acquisition.
 */
final class CollectibleOrder extends Model
{
    protected $table = 'collectible_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'item_id',
        'total_cents',
        'status',
        'type',
        'correlation_id',
    ];

    protected $casts = [
        'total_cents' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (CollectibleOrder $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        static::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (tenant()->id ?? 1));
        });
    }

    /**
     * Item purchased.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(CollectibleItem::class, 'item_id');
    }
}
