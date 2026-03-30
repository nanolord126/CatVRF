<?php declare(strict_types=1);

namespace App\Domains\Education\LanguageLearning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LanguageCourse extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'language_courses';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'school_id',
            'teacher_id',
            'title',
            'language',
            'level_from',
            'level_to',
            'syllabus',
            'price_total',
            'price_per_module',
            'max_students',
            'type',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'tags' => 'json',
            'price_total' => 'integer',
            'price_per_module' => 'integer',
            'max_students' => 'integer',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (int) (tenant('id') ?? 1);
            });

            static::addGlobalScope('tenant_id', function ($query) {
                if (tenant('id')) {
                    $query->where('tenant_id', tenant('id'));
                }
            });
        }

        public function school(): BelongsTo
        {
            return $this->belongsTo(LanguageSchool::class, 'school_id');
        }

        public function teacher(): BelongsTo
        {
            return $this->belongsTo(LanguageTeacher::class, 'teacher_id');
        }

        public function lessons(): HasMany
        {
            return $this->hasMany(LanguageLesson::class, 'course_id')->orderBy('scheduled_at');
        }

        public function enrollments(): HasMany
        {
            return $this->hasMany(LanguageEnrollment::class, 'course_id');
        }

        /**
         * Название уровня (например: "Итальянский (A1 -> B1)")
         */
        public function getFullLevelTitleAttribute(): string
        {
            return "{$this->language} ({$this->level_from} -> {$this->level_to})";
        }
}
