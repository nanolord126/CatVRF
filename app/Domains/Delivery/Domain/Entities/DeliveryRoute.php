<?php

declare(strict_types=1);

namespace App\Domains\Delivery\Domain\Entities;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class DeliveryRoute
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
final class DeliveryRoute extends Model
{
    use HasUuids;

    protected $table = 'delivery_routes';

    protected $fillable = [
        'delivery_id',
        'route_data',
        'estimated_time',
        'distance',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'route_data' => 'json',
    ];

    /**
     * Handle delivery operation.
     *
     * @throws \DomainException
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }
}
