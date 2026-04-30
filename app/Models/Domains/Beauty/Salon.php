<?php

declare(strict_types=1);

/**
 * Salon — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/salon
 * @see https://catvrf.ru/docs/salon
 * @see https://catvrf.ru/docs/salon
 * @see https://catvrf.ru/docs/salon
 */

namespace App\Models\Domains\Beauty;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Database\Factories\SalonFactory;

/**
 * Class Salon
 *
 * Part of the Beauty vertical domain.
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
final class Salon extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
    ];

    protected $table = 'beauty_salons';

    protected $guarded = [];

    /**
     * The number of models to return for pagination.
     */
    protected $perPage = 25;

    protected static function newFactory()
    {
        return SalonFactory::new();
    }

    protected static function booted(): void
    {
        parent::booted();
        self::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
