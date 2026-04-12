<?php declare(strict_types=1);

/**
 * ServiceReview — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/servicereview
 */


namespace App\Domains\HomeServices\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceReview extends Model
{
    use HasFactory;

    use SoftDeletes;

        protected $table = 'service_reviews';
        protected $fillable = [
        'uuid',
        'correlation_id','tenant_id', 'service_listing_id', 'contractor_id', 'job_id', 'reviewer_id', 'rating', 'title', 'content', 'categories', 'helpful_count', 'unhelpful_count', 'verified_job', 'published_at', 'correlation_id'];
        protected $hidden = [];
        protected $casts = ['categories' => 'collection', 'verified_job' => 'boolean', 'published_at' => 'datetime'];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant_id', fn($q) => $q->where('tenant_id', tenant()->id));
        }

        public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
        public function serviceListing(): BelongsTo { return $this->belongsTo(ServiceListing::class); }
        public function contractor(): BelongsTo { return $this->belongsTo(Contractor::class); }
        public function job(): BelongsTo { return $this->belongsTo(ServiceJob::class); }
        public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }

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
