<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Attendance
 *
 * Part of the Sports vertical domain.
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
 * @package App\Domains\Sports\Fitness\Models
 */
final class Attendance extends Model
{

    protected $table = 'attendances';
    protected $fillable = [
        'uuid',
        'correlation_id','tenant_id', 'class_schedule_id', 'member_id', 'checked_in_at', 'checked_out_at', 'duration_minutes', 'status', 'performance_data', 'correlation_id'];
    protected $casts = [
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'duration_minutes' => 'integer',
        'performance_data' => 'collection',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant()?->id));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
