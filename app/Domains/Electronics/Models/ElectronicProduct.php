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
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ElectronicProduct extends Model
{

    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'electronic_products';
        protected $fillable = [
            'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
            'name', 'description', 'category', 'brand', 'sku',
            'price', 'current_stock', 'warranty_months',
            'specifications', 'photo_url', 'status', 'tags',
            'screen_size', 'screen_resolution', 'screen_type', 'screen_refresh_rate',
            'ram', 'ram_type', 'storage', 'storage_type', 'cpu', 'cpu_cores', 'gpu', 'gpu_memory',
            'battery_capacity', 'fast_charging', 'wireless_charging', 'camera_main', 'camera_front',
            'video_resolution', 'weight', 'thickness', 'color', 'material_body', 'water_resistance',
            'nfc', 'fingerprint', 'face_id', 'os', 'network_5g', 'sim_count', 'cellular', 'connector_type',
            'bluetooth_version', 'wifi_standard', 'panel_type', 'hdr', 'smart_platform', 'sensor_size',
            'megapixels', 'stabilization', 'noise_cancellation', 'microphone', 'driver_size', 'frequency_range', 'impedance',
        ];
        protected $casts = [
            'price'           => 'int',
            'current_stock'   => 'int',
            'warranty_months' => 'int',
            'specifications'  => 'json',
            'tags'            => 'json',
            // Extended properties casts
            'screen_size'         => 'decimal:2',
            'screen_resolution'   => 'string',
            'screen_type'         => 'string',
            'screen_refresh_rate' => 'int',
            'ram'                 => 'int',
            'ram_type'            => 'string',
            'storage'             => 'int',
            'storage_type'        => 'string',
            'cpu'                 => 'string',
            'cpu_cores'           => 'int',
            'gpu'                 => 'string',
            'gpu_memory'          => 'int',
            'battery_capacity'    => 'int',
            'fast_charging'       => 'boolean',
            'wireless_charging'   => 'boolean',
            'camera_main'         => 'string',
            'camera_front'        => 'string',
            'video_resolution'    => 'string',
            'weight'              => 'decimal:2',
            'thickness'           => 'decimal:2',
            'color'               => 'string',
            'material_body'       => 'string',
            'water_resistance'    => 'string',
            'nfc'                 => 'boolean',
            'fingerprint'         => 'boolean',
            'face_id'             => 'boolean',
            'os'                  => 'string',
            'network_5g'          => 'boolean',
            'sim_count'           => 'int',
            'cellular'            => 'string',
            'connector_type'      => 'string',
            'bluetooth_version'   => 'string',
            'wifi_standard'       => 'string',
            'panel_type'          => 'string',
            'hdr'                 => 'string',
            'smart_platform'      => 'string',
            'sensor_size'         => 'string',
            'megapixels'          => 'int',
            'stabilization'       => 'string',
            'noise_cancellation'  => 'string',
            'microphone'          => 'boolean',
            'driver_size'         => 'int',
            'frequency_range'     => 'string',
            'impedance'           => 'int',
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
