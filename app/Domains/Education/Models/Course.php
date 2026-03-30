<?php declare(strict_types=1);

namespace App\Domains\Education\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Course extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'courses';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'title',
            'description',
            'level',
            'price_kopecks',
            'corporate_price_kopecks',
            'syllabus',
            'is_active',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'uuid' => 'string',
            'price_kopecks' => 'integer',
            'corporate_price_kopecks' => 'integer',
            'syllabus' => 'json',
            'is_active' => 'boolean',
            'tags' => 'json',
            'level' => 'string',
        ];

        protected $hidden = [
            'id',
            'tenant_id',
        ];

        /**
         * КАНОН 2026: Изоляция тенанта
         */
        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($builder) {
                if (auth()->check()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });

            static::creating(function (Course $course) {
                $course->uuid = $course->uuid ?? (string) Str::uuid();
                $course->tenant_id = $course->tenant_id ?? (int) tenant()->id;
                $course->correlation_id = $course->correlation_id ?? (string) Str::uuid();
            });
        }

        /**
         * Преподаватель курса
         */
        public function teacher(): BelongsTo
        {
            return $this->belongsTo(Teacher::class);
        }

        /**
         * Модули курса
         */
        public function modules(): HasMany
        {
            return $this->hasMany(CourseModule::class)->orderBy('order');
        }

        /**
         * Зачисления студентов
         */
        public function enrollments(): HasMany
        {
            return $this->hasMany(Enrollment::class);
        }

        /**
         * Отзывы
         */
        public function reviews(): HasMany
        {
            return $this->hasMany(CourseReview::class);
        }

        /**
         * Все уроки курса через модули
         */
        public function lessons()
        {
            return $this->hasManyThrough(Lesson::class, CourseModule::class);
        }
}
