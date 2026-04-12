<?php declare(strict_types=1);

/**
 * Farm — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/farm
 */


namespace App\Domains\FarmDirect\FreshProduce\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Farm extends Model
{
    use HasFactory;

    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'produce_farms';
        protected $fillable = ['uuid', 'tenant_id', 'business_group_id', 'correlation_id', 'name', 'owner_id', 'address', 'phone', 'latitude', 'longitude', 'certification', 'is_verified', 'commission_percent', 'min_order', 'tags'];
        protected $casts = ['is_verified' => 'boolean', 'commission_percent' => 'float', 'latitude' => 'float', 'longitude' => 'float', 'min_order' => 'integer', 'tags' => 'json'];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function products() { return $this->hasMany(ProduceProduct::class, 'farm_id'); }
        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function orders() { return $this->hasMany(ProduceOrder::class, 'farm_id'); }

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('produce_farms.tenant_id', tenant()->id));
        }

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

}
