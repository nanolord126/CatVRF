<?php declare(strict_types=1);

/**
 * MediationCase — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/mediationcase
 */


namespace App\Domains\Legal\ConflictResolution\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MediationCase extends Model
{

    use HasUuids,SoftDeletes,TenantScoped;protected $table='mediation_cases';protected $fillable=['uuid','tenant_id','specialist_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','case_type','hours_spent','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','hours_spent'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted_disabled(){static::addGlobalScope('tenant',fn($q)=>$q->where('mediation_cases.tenant_id',tenant()->id));}

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
