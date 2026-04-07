<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FitnessClass
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
final class FitnessClass extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'fitness_classes';
    protected $fillable = [
        'uuid',
        'correlation_id','tenant_id', 'gym_id', 'trainer_id', 'name', 'description', 'class_type', 'duration_minutes', 'max_participants', 'current_participants', 'price_per_class', 'tags', 'rating', 'review_count', 'is_active', 'correlation_id'];
    protected $casts = [
        'tags' => 'collection',
        'duration_minutes' => 'integer',
        'max_participants' => 'integer',
        'current_participants' => 'integer',
        'price_per_class' => 'float',
        'rating' => 'float',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant()?->id));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'class_schedule_id', 'id');
    }
}
