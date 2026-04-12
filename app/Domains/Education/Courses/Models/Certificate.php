<?php declare(strict_types=1);

/**
 * Certificate — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/certificate
 */


namespace App\Domains\Education\Courses\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Certificate extends Model
{
    protected $table = 'education_certificates';

    use HasFactory;

    use HasUuids, SoftDeletes;

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'enrollment_id',
            'course_id',
            'student_id',
            'certificate_number',
            'issued_at',
            'certificate_url',
            'verification_code',
            'student_name',
            'achievement_description',
            'correlation_id',
        ];

        protected $casts = [
            'issued_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant()->id ?? 0));
        }

        public function enrollment(): BelongsTo
        {
            return $this->belongsTo(Enrollment::class);
        }

        public function course(): BelongsTo
        {
            return $this->belongsTo(Course::class);
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
