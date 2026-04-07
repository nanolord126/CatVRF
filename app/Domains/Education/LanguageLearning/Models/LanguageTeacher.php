<?php declare(strict_types=1);

namespace App\Domains\Education\LanguageLearning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LanguageTeacher extends Model
{
    use HasFactory;

    protected $table = 'language_teachers';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'school_id',
            'full_name',
            'native_language',
            'teaching_languages',
            'bio',
            'experience_years',
            'rating',
            'hourly_rate',
            'availability',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'teaching_languages' => 'json',
            'availability' => 'json',
            'tags' => 'json',
            'rating' => 'float',
            'hourly_rate' => 'integer',
            'experience_years' => 'integer',
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

        public function school(): BelongsTo
        {
            return $this->belongsTo(LanguageSchool::class, 'school_id');
        }

        public function courses(): HasMany
        {
            return $this->hasMany(LanguageCourse::class, 'teacher_id');
        }

        /**
         * Расчет полной стоимости часа в рублях (из копеек).
         */
        public function getRateInRublesAttribute(): float
        {
            return $this->hourly_rate / 100;
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(LanguageReview::class, 'reviewable_id')
                ->where('reviewable_type', self::class);
        }
}
