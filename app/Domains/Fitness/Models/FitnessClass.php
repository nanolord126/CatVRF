<?php declare(strict_types=1);

namespace App\Domains\Fitness\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FitnessClass extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_classes';
    protected $fillable = ['tenant_id', 'gym_id', 'trainer_id', 'name', 'description', 'class_type', 'duration_minutes', 'max_participants', 'current_participants', 'price_per_class', 'tags', 'rating', 'review_count', 'is_active', 'correlation_id'];
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
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id')));
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
