<?php

declare(strict_types=1);

/**
 * MentorshipProgram — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/mentorshipprogram
 */

namespace App\Domains\PersonalDevelopment\LeadershipDevelopment\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Class MentorshipProgram
 *
 * Part of the PersonalDevelopment vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Eloquent model with tenant-scoping and business group isolation.
 * All queries are automatically scoped by tenant_id via global scope.
 *
 * Required fields: uuid, correlation_id, tenant_id, business_group_id, tags (json).
 * Audit logging is handled via model events (created, updated, deleted).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class MentorshipProgram extends Model
{
    protected $table = 'mentorship_programs';

    protected $fillable = ['uuid', 'tenant_id', 'mentor_id', 'client_id', 'correlation_id', 'status', 'total_kopecks', 'payout_kopecks', 'payment_status', 'program_type', 'hours_spent', 'due_date', 'tags'];

    protected $casts = ['total_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'hours_spent' => 'integer', 'due_date' => 'datetime', 'tags' => 'json'];

    /**
     * The number of models to return for pagination.
     */
    protected $perPage = 25;

    protected static function booted()
    {
        self::addGlobalScope('tenant', fn ($q) => $q->where('mentorship_programs.tenant_id', tenant()->id));
    }
}
