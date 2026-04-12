<?php declare(strict_types=1);

/**
 * ConsultingProject — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/consultingproject
 */


namespace App\Domains\Consulting\ConsultingFirm\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConsultingProject extends Model
{
    use HasFactory;

    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'consulting_projects';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'consultant_id',
            'client_id',
            'correlation_id',
            'status',
            'total_kopecks',
            'payout_kopecks',
            'payment_status',
            'project_type',
            'consulting_hours',
            'due_date',
            'tags',
        ];

        protected $casts = [
            'total_kopecks' => 'integer',
            'payout_kopecks' => 'integer',
            'consulting_hours' => 'integer',
            'due_date' => 'datetime',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('consulting_projects.tenant_id', tenant()->id));
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
