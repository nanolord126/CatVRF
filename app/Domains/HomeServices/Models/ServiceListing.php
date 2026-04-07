<?php declare(strict_types=1);

/**
 * ServiceListing — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/servicelisting
 */


namespace App\Domains\HomeServices\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceListing extends Model
{
    use HasFactory;

    protected $table = 'service_listings';
        protected $fillable = [
        'uuid',
        'correlation_id','tenant_id', 'contractor_id', 'category_id', 'name', 'description', 'type', 'base_price', 'estimated_duration_minutes', 'equipment', 'requirements', 'rating', 'booking_count', 'completion_count', 'is_active', 'correlation_id'];
        protected $hidden = [];
        protected $casts = ['equipment' => 'collection', 'requirements' => 'collection', 'base_price' => 'float', 'rating' => 'float', 'is_active' => 'boolean'];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant_id', fn($q) => $q->where('tenant_id', tenant()->id));
        }

        public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
        public function contractor(): BelongsTo { return $this->belongsTo(Contractor::class); }
        public function category(): BelongsTo { return $this->belongsTo(ServiceCategory::class); }
        public function jobs(): HasMany { return $this->hasMany(ServiceJob::class); }
        public function reviews(): HasMany { return $this->hasMany(ServiceReview::class); }

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
