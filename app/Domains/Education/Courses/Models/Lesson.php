<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Lesson extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes;

        protected $fillable = [
            'tenant_id',
            'course_id',
            'title',
            'description',
            'content',
            'video_url',
            'duration_minutes',
            'sort_order',
            'is_published',
            'resources',
            'correlation_id',
        ];

        protected $casts = [
            'resources' => 'json',
            'is_published' => 'boolean',
        ];

        public function booted(): void
        {
            static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant('id') ?? 0));
        }

        public function course(): BelongsTo
        {
            return $this->belongsTo(Course::class);
        }

        public function progress(): HasMany
        {
            return $this->hasMany(LessonProgress::class);
        }
}
