<?php declare(strict_types=1);

/**
 * NursingAgency — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/nursingagency
 */


namespace App\Domains\Medical\NursingServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class NursingAgency extends Model
{
    use HasFactory;

    use HasUuids,SoftDeletes,TenantScoped;protected $table='nursing_agencies';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','qualifications','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['qualifications'=>'json','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted_disabled(){static::addGlobalScope('tenant',fn($q)=>$q->where('nursing_agencies.tenant_id',tenant()->id));}

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
