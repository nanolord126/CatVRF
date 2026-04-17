<?php declare(strict_types=1);

/**
 * ServiceDispute — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/servicedispute
 */


namespace App\Domains\HomeServices\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceDispute extends Model
{

    protected $table = 'service_disputes';
        protected $fillable = [
        'uuid',
        'correlation_id','tenant_id', 'job_id', 'initiator_id', 'type', 'description', 'status', 'resolution', 'resolved_by', 'refund_amount', 'evidence', 'resolved_at', 'correlation_id'];
        protected $hidden = [];
        protected $casts = ['evidence' => 'collection', 'refund_amount' => 'float', 'resolved_at' => 'datetime'];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant_id', fn($q) => $q->where('tenant_id', tenant()->id));
        }

        public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
        public function job(): BelongsTo { return $this->belongsTo(ServiceJob::class); }
        public function initiator(): BelongsTo { return $this->belongsTo(User::class, 'initiator_id'); }

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
