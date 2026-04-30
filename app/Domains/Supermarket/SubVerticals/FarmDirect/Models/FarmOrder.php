<?php

declare(strict_types=1);

/**
 * FarmOrder — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/farmorder
 */

namespace App\Domains\Supermarket\SubVerticals\FarmDirect\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Database\Factories\FarmOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FarmOrder extends Model
{
    use HasFactory;
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

    protected $table = 'farm_orders';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'business_group_id',
        'farm_id',
        'customer_id',
        'status',
        'total_price',
        'delivery_address',
        'delivery_date',
        'notes',
        'tags',
    ];

    protected $casts = [
        'tags'            => 'json',
    ];

    protected static function newFactory(): FarmOrderFactory
    {
        return new FarmOrderFactory();
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
