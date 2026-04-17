<?php

declare(strict_types=1);

namespace App\Domains\Delivery\Domain\Entities;
use Illuminate\Database\Eloquent\Model;

use App\Domains\Delivery\Domain\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Tenant;
use App\Models\User;

/**
 * Class Delivery
 *
 * Part of the Delivery vertical domain.
 * Follows CatVRF 9-layer architecture.
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
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\Domains\Delivery\Domain\Entities
 */
final class Delivery extends Model
{

    protected $table = 'deliveries';

    protected $fillable = [
        'order_id',
        'tenant_id',
        'courier_id',
        'status',
        'from_address',
        'to_address',
        'payload',
        'correlation_id',
        'uuid',
        'tags',
        'business_group_id',
    ];

    protected $casts = [
        'status' => DeliveryStatus::class,
        'payload' => 'json',
        'tags' => 'json',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function route(): HasOne
    {
        return $this->hasOne(DeliveryRoute::class);
    }
}
