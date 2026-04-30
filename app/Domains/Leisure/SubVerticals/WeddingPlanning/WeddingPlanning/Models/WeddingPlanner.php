<?php

declare(strict_types=1);

/**
 * WeddingPlanner — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/weddingplanner
 */

namespace App\Domains\Leisure\SubVerticals\WeddingPlanning\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Class WeddingPlanner
 *
 * Part of the WeddingPlanning vertical domain.
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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class WeddingPlanner extends Model
{
    protected $table = 'wedding_planners';

    protected $fillable = ['uuid', 'tenant_id', 'user_id', 'correlation_id', 'name', 'services', 'base_price_kopecks', 'price_per_guest', 'rating', 'is_verified', 'tags'];

    protected $casts = ['services' => 'json', 'base_price_kopecks' => 'integer', 'price_per_guest' => 'integer', 'rating' => 'float', 'is_verified' => 'boolean', 'tags' => 'json'];

    /**
     * The number of models to return for pagination.
     */
    protected $perPage = 25;

    protected static function booted()
    {
        self::addGlobalScope('tenant', fn ($q) => $q->where('wedding_planners.tenant_id', tenant()->id));
    }
}
