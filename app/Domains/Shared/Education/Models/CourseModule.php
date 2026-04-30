<?php

declare(strict_types=1);

namespace App\Domains\Education\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class CourseModule extends Model
{
    use TenantScoped;

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
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
