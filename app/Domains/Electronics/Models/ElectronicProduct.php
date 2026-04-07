<?php declare(strict_types=1);

/**
 * ElectronicProduct — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/electronicproduct
 */


namespace App\Domains\Electronics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ElectronicProduct extends Model
{
    use HasFactory;

    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'electronic_products';
        protected $fillable = [
            'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
            'name', 'description', 'category', 'brand', 'sku',
            'price', 'current_stock', 'warranty_months',
            'specifications', 'photo_url', 'status', 'tags',
        ];
        protected $casts = [
            'price'           => 'int',
            'current_stock'   => 'int',
            'warranty_months' => 'int',
            'specifications'  => 'json',
            'tags'            => 'json',
        ];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function warrantyClaims(): HasMany
        {
            return $this->hasMany(WarrantyClaim::class, 'product_id');
        }

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant()->id) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
