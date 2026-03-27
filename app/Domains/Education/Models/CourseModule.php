<?php

declare(strict_types=1);

namespace App\Domains\Education\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * КАНОН 2026: Модель модуля курса (Education).
 * Структурирование курса на части.
 */
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
}
