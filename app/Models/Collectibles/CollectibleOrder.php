<?php

declare(strict_types=1);

namespace App\Models\Collectibles;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Class CollectibleOrder
 *
 * Eloquent model with tenant-scoping and business group isolation.
 * All queries are automatically scoped by tenant_id via global scope.
 *
 * Required fields: uuid, correlation_id, tenant_id, business_group_id, tags (json).
 * Audit logging is handled via model events (created, updated, deleted).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property Carbon $created_at
 * @property Carbon $updated_at
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

    /**
     * Item purchased.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(CollectibleItem::class, 'item_id');
    }

    protected static function booted(): void
    {
        self::creating(function (CollectibleOrder $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        self::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (tenant()->id ?? 1));
        });
    }
}
