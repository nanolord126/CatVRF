<?php declare(strict_types=1);

namespace App\Domains\Education\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Lesson extends Model
{

    protected $table = 'lessons';

        protected $fillable = [
            'uuid',
            'course_id',
            'title',
            'type',
            'content',
            'meeting_url',
            'duration_minutes',
            'order',
            'correlation_id',
        ];

        protected $casts = [
            'uuid' => 'string',
            'duration_minutes' => 'integer',
            'order' => 'integer',
            'type' => 'string',
        ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }


        protected $hidden = [
            'id',
        ];

        /**
         * КАНОН 2026: Инициализация UUID
         */
        

        /**
         * Модуль курса, к которому относится урок
         */
        public function module(): BelongsTo
        {
            return $this->belongsTo(CourseModule::class, 'course_module_id');
        }

        /**
         * Живые видеозвонки в рамках урока
         */
        public function videoCalls(): HasMany
        {
            return $this->hasMany(VideoCall::class);
        }

        /**
         * Дочерний метод получения тенанта через связи (для безопасности)
         */
        public function getTenantIdAttribute(): int
        {
            return (int) $this->module->course->tenant_id;
        }
}
