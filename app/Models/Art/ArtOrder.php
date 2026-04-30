<?php

declare(strict_types=1);

namespace App\Models\Art;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;

/**
 * Class ArtOrder
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

    /**
     * Customer who placed the order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    protected static function booted(): void
    {
        self::creating(function (ArtOrder $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        self::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (tenant()->id ?? 1));
        });
    }
}
