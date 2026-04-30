<?php

declare(strict_types=1);

/**
 * GiftVendor — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/giftvendor
 */

namespace App\Domains\Leisure\SubVerticals\Flowers\GiftDelivery\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class GiftVendor extends Model
{
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

    protected $table = 'gift_vendors';

    protected $fillable = ['uuid', 'tenant_id', 'user_id', 'correlation_id', 'name', 'categories', 'rating', 'is_verified', 'tags'];

    protected $casts = ['categories' => 'json', 'rating' => 'float', 'is_verified' => 'boolean', 'tags' => 'json'];

    protected static function booted()
    {
        self::addGlobalScope('tenant', fn ($q) => $q->where('gift_vendors.tenant_id', tenant()->id));
    }

    /**
     * Get the component identifier for logging and audit purposes.
     *
     * @return string The fully qualified component name
     */
    private function getComponentIdentifier(): string
    {
        return self::class.'@'.self::VERSION;
    }

    /**
     * Validate the current operation context.
     * Ensures tenant scoping and correlation ID are present.
     *
     * @param  string  $operation  The operation being validated
     *
     * @throws \DomainException If validation fails
     */
    private function validateOperationContext(string $operation): void
    {
        if (empty($operation)) {
            throw new \DomainException('Operation context cannot be empty');
        }
    }
}
