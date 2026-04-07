<?php declare(strict_types=1);

/**
 * SoftwareProject — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/softwareproject
 */


namespace App\Domains\Freelance\SoftwareDevelopment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SoftwareProject extends Model
{
    use HasFactory;

    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'software_projects';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'developer_id',
            'client_id',
            'correlation_id',
            'status',
            'total_kopecks',
            'payout_kopecks',
            'payment_status',
            'project_type',
            'development_hours',
            'due_date',
            'tags',
        ];

        protected $casts = [
            'total_kopecks' => 'integer',
            'payout_kopecks' => 'integer',
            'development_hours' => 'integer',
            'due_date' => 'datetime',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('software_projects.tenant_id', tenant()->id));
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
