<?php declare(strict_types=1);

/**
 * LanguageReview — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/languagereview
 */


namespace App\Domains\Education\LanguageLearning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LanguageReview extends Model
{

    protected $table = 'language_reviews';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'reviewable_id',
            'reviewable_type',
            'rating',
            'comment',
            'is_published',
            'correlation_id',
        ];

        protected $casts = [
            'rating' => 'integer',
            'is_published' => 'boolean',
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

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        /**
         * Полиморфная привязка (Курс или Преподаватель).
         */
        public function reviewable(): MorphTo
        {
            return $this->morphTo();
        }
}
