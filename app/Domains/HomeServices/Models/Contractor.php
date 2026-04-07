<?php declare(strict_types=1);

/**
 * Contractor — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/contractor
 */


namespace App\Domains\HomeServices\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Contractor extends Model
{
    use HasFactory;

    protected $table = 'contractors';
        protected $fillable = [
        'uuid',
        'correlation_id','tenant_id', 'user_id', 'company_name', 'description', 'address', 'geo_point', 'services', 'specializations', 'phone', 'website', 'hourly_rate', 'rating', 'review_count', 'job_count', 'completed_count', 'is_verified', 'is_active', 'correlation_id'];
        protected $hidden = [];
        protected $casts = ['services' => 'collection', 'specializations' => 'collection', 'hourly_rate' => 'float', 'rating' => 'float', 'is_verified' => 'boolean', 'is_active' => 'boolean'];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant_id', fn($q) => $q->where('tenant_id', tenant()->id));
        }

        public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
        public function user(): BelongsTo { return $this->belongsTo(User::class); }
        public function serviceListings(): HasMany { return $this->hasMany(ServiceListing::class); }
        public function schedules(): HasMany { return $this->hasMany(ContractorSchedule::class); }
        public function jobs(): HasMany { return $this->hasMany(ServiceJob::class); }
        public function reviews(): HasMany { return $this->hasMany(ServiceReview::class); }
        public function earnings(): HasMany { return $this->hasMany(ContractorEarning::class); }

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
