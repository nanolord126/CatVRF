<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LessonProgress extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes;

        protected $fillable = [
            'tenant_id',
            'enrollment_id',
            'lesson_id',
            'is_completed',
            'completed_at',
            'watch_time_seconds',
            'completion_percent',
            'correlation_id',
        ];

        protected $casts = [
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
            'completion_percent' => 'float',
        ];

        public function booted(): void
        {
            static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant('id') ?? 0));
        }

        public function enrollment(): BelongsTo
        {
            return $this->belongsTo(Enrollment::class);
        }

        public function lesson(): BelongsTo
        {
            return $this->belongsTo(Lesson::class);
        }
}
