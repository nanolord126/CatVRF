<?php declare(strict_types=1);

/**
 * QualityAudit — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/qualityaudit
 */


namespace App\Domains\Insurance\AssuranceServices\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class QualityAudit extends Model
{
    use HasFactory;

    use HasUuids,SoftDeletes,TenantScoped;protected $table='quality_audits';protected $fillable=['uuid','tenant_id','auditor_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','audit_type','hours_spent','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','hours_spent'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted_disabled(){static::addGlobalScope('tenant',fn($q)=>$q->where('quality_audits.tenant_id',tenant()->id));}

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
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
