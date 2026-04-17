<?php declare(strict_types=1);

namespace App\Domains\Education\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CourseModule extends Model
{

    protected $table = 'course_modules';

        protected $fillable = [
            'uuid',
            'course_id',
            'title',
            'order',
            'description',
            'correlation_id',
        ];

        protected $casts = [
            'uuid' => 'string',
            'order' => 'integer',
        ];

        protected $hidden = [
            'id',
        ];

        /**
         * Курс, к которому относится модуль
         */
        public function course(): BelongsTo
        {
            return $this->belongsTo(Course::class);
        }

        /**
         * Уроки в рамках модуля
         */
        public function lessons()
        {
            return $this->hasMany(Lesson::class)->orderBy('order');
        }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

}