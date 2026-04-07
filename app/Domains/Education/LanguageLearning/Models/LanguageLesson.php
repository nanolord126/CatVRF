<?php declare(strict_types=1);

namespace App\Domains\Education\LanguageLearning\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LanguageLesson extends Model
{
    use HasFactory;

    protected $table = 'language_lessons';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'course_id',
            'topic',
            'description',
            'scheduled_at',
            'duration_minutes',
            'status',
            'homework',
            'correlation_id',
        ];

        protected $casts = [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (int) (tenant()->id ?? 1);
            });

            static::addGlobalScope('tenant_id', function ($query) {
                if (tenant()->id) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function course(): BelongsTo
        {
            return $this->belongsTo(LanguageCourse::class, 'course_id');
        }

        public function videoCall(): HasOne
        {
            return $this->hasOne(LanguageVideoCall::class, 'lesson_id');
        }

        /**
         * Проверка: идет ли занятие сейчас.
         */
        public function isNowAttribute(): bool
        {
            $end = $this->scheduled_at->addMinutes($this->duration_minutes);
            return Carbon::now()->between($this->scheduled_at, $end) && $this->status === 'active';
        }
}
