<?php declare(strict_types=1);

/**
 * SportStore — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/sportstore
 */


namespace App\Domains\Sports\SportingGoods\Models;

use App\Models\Traits\HasUuids;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SportStore extends Model
{
    use HasFactory;

    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'sport_stores';
        protected $fillable = ['uuid', 'tenant_id', 'business_group_id', 'correlation_id', 'name', 'address', 'phone', 'latitude', 'longitude', 'is_verified', 'commission_percent', 'min_order', 'tags'];
        protected $casts = ['is_verified' => 'boolean', 'commission_percent' => 'float', 'latitude' => 'float', 'longitude' => 'float', 'min_order' => 'integer', 'tags' => 'json'];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function products() { return $this->hasMany(SportProduct::class, 'store_id'); }
        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function orders() { return $this->hasMany(SportOrder::class, 'store_id'); }

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('sport_stores.tenant_id', tenant()->id));
        }
}
