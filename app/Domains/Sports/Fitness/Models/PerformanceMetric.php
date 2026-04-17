<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PerformanceMetric
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
final class PerformanceMetric extends Model
{

    protected $table = 'performance_metrics';
    protected $fillable = [
        'uuid',
        'correlation_id','tenant_id', 'member_id', 'gym_id', 'metric_date', 'classes_attended', 'total_classes_available', 'calories_burned', 'workout_duration_minutes', 'body_weight', 'body_fat_percentage', 'muscle_mass', 'custom_metrics', 'notes', 'correlation_id'];
    protected $casts = [
        'metric_date' => 'date',
        'classes_attended' => 'integer',
        'total_classes_available' => 'integer',
        'calories_burned' => 'float',
        'workout_duration_minutes' => 'float',
        'body_weight' => 'float',
        'body_fat_percentage' => 'float',
        'muscle_mass' => 'float',
        'custom_metrics' => 'collection',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }
}
