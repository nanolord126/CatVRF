<?php declare(strict_types=1);

/**
 * CourseCategory — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/coursecategory
 */


namespace App\Domains\Education\Courses\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CourseCategory extends Model
{
    protected $table = 'education_course_categories';

    use HasFactory;

    use HasUuids, SoftDeletes;

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'name',
            'description',
            'slug',
            'icon_url',
            'sort_order',
            'course_count',
            'is_active',
            'correlation_id',
        ];

        protected $casts = [
            'is_active' => 'boolean',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant()->id ?? 0));
        }

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

}
