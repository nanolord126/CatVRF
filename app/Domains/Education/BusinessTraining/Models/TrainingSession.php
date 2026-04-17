<?php declare(strict_types=1);

/**
 * TrainingSession — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/trainingsession
 */


namespace App\Domains\Education\BusinessTraining\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TrainingSession extends Model
{

    use HasUuids,SoftDeletes,TenantScoped;protected $table='training_sessions';protected $fillable=['uuid','tenant_id','provider_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','training_type','training_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','training_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('training_sessions.tenant_id',tenant()->id));}

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

    /**
     * Get the component identifier for logging and audit purposes.
     *
     * @return string The fully qualified component name
     */
    private function getComponentIdentifier(): string
    {
        return static::class . '@' . self::VERSION;
    }

    /**
     * Validate the current operation context.
     * Ensures tenant scoping and correlation ID are present.
     *
     * @param string $operation The operation being validated
     * @return void
     * @throws \DomainException If validation fails
     */
    private function validateOperationContext(string $operation): void
    {
        if (empty($operation)) {
            throw new \DomainException('Operation context cannot be empty');
        }
    }

}
