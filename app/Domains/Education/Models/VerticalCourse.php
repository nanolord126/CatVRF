<?php declare(strict_types=1);

namespace App\Domains\Education\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VerticalCourse - связывает курсы с бизнес-вертикалями для B2B обучения
 * 
 * Позволяет создавать специализированные курсы для конкретных вертикалей:
 * - Beauty: обучение для салонов красоты, мастеров
 * - Hotels: обучение для отелей, персонала
 * - Flowers: обучение для флористов
 * - и другие вертикали
 */
final class VerticalCourse extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\VerticalCourseFactory::new();
    }

    protected $table = 'vertical_courses';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'course_id',
        'vertical',
        'target_role',
        'difficulty_level',
        'duration_hours',
        'is_required',
        'prerequisites',
        'learning_objectives',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'is_required' => 'boolean',
        'duration_hours' => 'integer',
        'prerequisites' => 'json',
        'learning_objectives' => 'json',
        'metadata' => 'json',
    ];

    /**
     * КАНОН 2026: Изоляция тенанта
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function (VerticalCourse $verticalCourse) {
            $verticalCourse->uuid = $verticalCourse->uuid ?? (string) \Illuminate\Support\Str::uuid();
            $verticalCourse->tenant_id = $verticalCourse->tenant_id ?? (int) tenant()->id;
            $verticalCourse->correlation_id = $verticalCourse->correlation_id ?? (string) \Illuminate\Support\Str::uuid();
        });
    }

    /**
     * Связанный курс
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Получить курсы для конкретной вертикали
     */
    public function scopeForVertical($query, string $vertical)
    {
        return $query->where('vertical', $vertical);
    }

    /**
     * Получить обязательные курсы
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Получить курсы по уровню сложности
     */
    public function scopeByDifficulty($query, string $level)
    {
        return $query->where('difficulty_level', $level);
    }

    /**
     * Получить курсы по целевой роли
     */
    public function scopeByTargetRole($query, string $role)
    {
        return $query->where('target_role', $role);
    }
}
