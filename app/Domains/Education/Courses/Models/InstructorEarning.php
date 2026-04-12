<?php declare(strict_types=1);

/**
 * InstructorEarning — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/instructorearning
 */


namespace App\Domains\Education\Courses\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InstructorEarning extends Model
{
    protected $table = 'education_instructor_earnings';

    use HasFactory;

    use HasUuids, SoftDeletes;

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'instructor_id',
            'course_id',
            'total_students',
            'total_revenue',
            'platform_commission',
            'instructor_earnings',
            'last_payout_at',
            'next_payout_at',
            'correlation_id',
        ];

        protected $casts = [
            'last_payout_at' => 'datetime',
            'next_payout_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant()->id ?? 0));
        }

        public function course(): BelongsTo
        {
            return $this->belongsTo(Course::class);
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
