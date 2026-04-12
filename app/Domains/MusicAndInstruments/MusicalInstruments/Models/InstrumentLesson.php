<?php declare(strict_types=1);

/**
 * InstrumentLesson — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/instrumentlesson
 */


namespace App\Domains\MusicAndInstruments\MusicalInstruments\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\TenantScoped;

use App\Models\Traits\HasUuids;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class InstrumentLesson
 *
 * Part of the MusicAndInstruments vertical domain.
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
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\Domains\MusicAndInstruments\MusicalInstruments\Models
 */
final class InstrumentLesson extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'instrument_lessons';
    protected $fillable = ['uuid', 'tenant_id', 'teacher_id', 'student_id', 'correlation_id', 'status', 'total_kopecks', 'payout_kopecks', 'payment_status', 'instrument', 'lesson_hours', 'lesson_date', 'tags'];
    protected $casts = ['total_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'lesson_hours' => 'integer', 'lesson_date' => 'datetime', 'tags' => 'json'];

    protected static function booted()
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('instrument_lessons.tenant_id', tenant()->id));
    }

    /**
     * The number of models to return for pagination.
     */
    protected $perPage = 25;

}
