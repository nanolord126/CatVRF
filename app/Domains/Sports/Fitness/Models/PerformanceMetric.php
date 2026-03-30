<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PerformanceMetric extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'performance_metrics';
        protected $fillable = ['tenant_id', 'member_id', 'gym_id', 'metric_date', 'classes_attended', 'total_classes_available', 'calories_burned', 'workout_duration_minutes', 'body_weight', 'body_fat_percentage', 'muscle_mass', 'custom_metrics', 'notes', 'correlation_id'];
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
            static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id')));
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
