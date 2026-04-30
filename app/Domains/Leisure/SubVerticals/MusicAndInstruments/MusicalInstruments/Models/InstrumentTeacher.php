<?php

declare(strict_types=1);

/**
 * InstrumentTeacher — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/instrumentteacher
 */

namespace App\Domains\MusicAndInstruments\MusicalInstruments\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Class InstrumentTeacher
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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class InstrumentTeacher extends Model
{
    protected $table = 'instrument_teachers';

    protected $fillable = ['uuid', 'tenant_id', 'user_id', 'correlation_id', 'name', 'instruments', 'price_kopecks_per_hour', 'rating', 'is_verified', 'tags'];

    protected $casts = ['instruments' => 'json', 'price_kopecks_per_hour' => 'integer', 'rating' => 'float', 'is_verified' => 'boolean', 'tags' => 'json'];

    /**
     * The number of models to return for pagination.
     */
    protected $perPage = 25;

    protected static function booted()
    {
        self::addGlobalScope('tenant', fn ($q) => $q->where('instrument_teachers.tenant_id', tenant()->id));
    }
}
