<?php declare(strict_types=1);

namespace App\Domains\Education\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CourseModule extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
}
