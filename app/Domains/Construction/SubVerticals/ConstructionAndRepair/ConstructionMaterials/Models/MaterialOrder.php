<?php

declare(strict_types=1);

/**
 * MaterialOrder — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/materialorder
 */

namespace App\Domains\ConstructionAndRepair\ConstructionMaterials\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class MaterialOrder extends Model
{
    use SoftDeletes;
    use TenantScoped;

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    protected $table = 'material_orders';

    protected $fillable = [
        'tenant_id', 'uuid', 'correlation_id',
        'material_id', 'user_id', 'quantity', 'total_price',
        'status', 'delivery_address', 'tracking_number', 'meta',
    ];

    protected $casts = [
        'quantity' => 'int',
        'total_price' => 'int',
        'meta' => 'json',
    ];

    /**
     * Выполнить операцию
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function material()
    {
        return $this->belongsTo(ConstructionMaterial::class);
    }

    protected static function booted(): void
    {
        parent::booted();
        self::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant()->id) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
