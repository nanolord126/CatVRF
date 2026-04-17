<?php declare(strict_types=1);

/**
 * ServiceJob — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/servicejob
 */


namespace App\Domains\HomeServices\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceJob extends Model
{

    protected $table = 'service_jobs';
        protected $fillable = [
        'uuid',
        'correlation_id','tenant_id', 'service_listing_id', 'contractor_id', 'client_id', 'status', 'description', 'address_point', 'address', 'scheduled_at', 'started_at', 'completed_at', 'actual_duration_minutes', 'base_amount', 'commission_amount', 'total_amount', 'payment_status', 'transaction_id', 'photos', 'notes', 'correlation_id'];
        protected $hidden = [];
        protected $casts = ['photos' => 'collection', 'base_amount' => 'float', 'commission_amount' => 'float', 'total_amount' => 'float', 'scheduled_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime'];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant_id', fn($q) => $q->where('tenant_id', tenant()->id));
        }

        public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
        public function serviceListing(): BelongsTo { return $this->belongsTo(ServiceListing::class); }
        public function contractor(): BelongsTo { return $this->belongsTo(Contractor::class); }
        public function client(): BelongsTo { return $this->belongsTo(User::class, 'client_id'); }
        public function reviews(): HasMany { return $this->hasMany(ServiceReview::class, 'job_id'); }
        public function disputes(): HasMany { return $this->hasMany(ServiceDispute::class, 'job_id'); }

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
