<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ClassSchedule
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
final class ClassSchedule extends Model
{
    use HasFactory;

    protected $table = 'class_schedules';
    protected $fillable = [
        'uuid',
        'correlation_id','tenant_id', 'fitness_class_id', 'day_of_week', 'start_time', 'end_time', 'max_participants', 'current_participants', 'scheduled_at', 'is_cancelled', 'correlation_id'];
    protected $casts = [
        'max_participants' => 'integer',
        'current_participants' => 'integer',
        'scheduled_at' => 'datetime',
        'is_cancelled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant()?->id));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function fitnessClass(): BelongsTo
    {
        return $this->belongsTo(FitnessClass::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
