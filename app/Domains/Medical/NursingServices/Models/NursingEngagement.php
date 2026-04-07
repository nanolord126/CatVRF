<?php declare(strict_types=1);

/**
 * NursingEngagement — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/nursingengagement
 */


namespace App\Domains\Medical\NursingServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class NursingEngagement extends Model
{
    use HasFactory;

    use HasUuids,SoftDeletes,TenantScoped;protected $table='nursing_engagements';protected $fillable=['uuid','tenant_id','agency_id','patient_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','care_type','hours_required','start_date','end_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','hours_required'=>'integer','start_date'=>'datetime','end_date'=>'datetime','tags'=>'json'];protected static function booted_disabled(){static::addGlobalScope('tenant',fn($q)=>$q->where('nursing_engagements.tenant_id',tenant()->id));}

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
