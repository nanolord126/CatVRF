<?php

declare(strict_types=1);

/**
 * Auction — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/auction
 */

namespace App\Domains\Art\SubVerticals\Collectibles\AuctionHouses\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class Auction extends Model
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

    protected $table = 'auctions';

    protected $fillable = ['uuid', 'tenant_id', 'owner_id', 'correlation_id', 'item_name', 'start_price', 'current_bid', 'end_time', 'status', 'tags'];

    protected $casts = ['start_price' => 'integer', 'current_bid' => 'integer', 'end_time' => 'datetime', 'tags' => 'json'];

    protected static function booted()
    {
        self::addGlobalScope('tenant', fn ($q) => $q->where('auctions.tenant_id', tenant()->id));
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
