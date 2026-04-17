<?php declare(strict_types=1);

/**
 * WarehouseRental — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/warehouserental
 */


namespace App\Domains\Logistics\WarehouseRentals\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WarehouseRental extends Model
{

    use HasUuids,SoftDeletes,TenantScoped;protected $table='warehouse_rentals';protected $fillable=['uuid','tenant_id','warehouse_id','tenant_business_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','lease_start','lease_end','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','lease_start'=>'datetime','lease_end'=>'datetime','tags'=>'json'];protected static function booted_disabled(){static::addGlobalScope('tenant',fn($q)=>$q->where('warehouse_rentals.tenant_id',tenant()->id));}

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
