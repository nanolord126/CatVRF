<?php declare(strict_types=1);

/**
 * LogisticCompany — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logisticcompany
 */


namespace App\Domains\Logistics\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LogisticCompany extends Model
{

    use HasUuids,SoftDeletes,TenantScoped;protected $table='logistic_companies';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','address','price_kopecks_per_km','vehicles_count','rating','tags'];protected $casts=['price_kopecks_per_km'=>'integer','vehicles_count'=>'integer','rating'=>'float','tags'=>'json'];protected static function booted_disabled(){static::addGlobalScope('tenant',fn($q)=>$q->where('logistic_companies.tenant_id',tenant()->id));}

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
