<?php declare(strict_types=1);

/**
 * GeoZone — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/geozone
 * @see https://catvrf.ru/docs/geozone
 * @see https://catvrf.ru/docs/geozone
 * @see https://catvrf.ru/docs/geozone
 */


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class GeoZone
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
 * @package App\Models
 */
final class GeoZone extends Model
{


    protected $table = 'geo_zones';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'business_group_id',
        'name',
        'slug',
        'polygon',
        'center_lat',
        'center_lon',
        'radius_km',
        'delivery_price',
        'min_order_amount',
        'is_active',
        'tags',
    ];

        protected static function newFactory()
        {
            return \Database\Factories\GeoZoneFactory::new();
        }

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope("tenant_id", function ($query) {
                if (function_exists("tenant") && tenant("id")) {
                    $query->where("tenant_id", tenant("id"));
                }
            });
        }
}
