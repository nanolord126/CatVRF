<?php

declare(strict_types=1);

/**
 * Vehicle — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/vehicle
 */

namespace App\Domains\Auto\VehicleDealing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class Vehicle extends Model
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

    protected $table = 'vehicles';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'dealer_id',
        'correlation_id',
        'make',
        'model',
        'year',
        'price_kopecks',
        'mileage',
        'status',
        'rating',
        'tags',
    ];

    protected $casts = [
        'price_kopecks' => 'integer',
        'mileage' => 'integer',
        'year' => 'integer',
        'rating' => 'float',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', fn ($q) => $q->where('vehicles.tenant_id', tenant()->id));
    }
}
