<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsReview extends Model
{

    use HasFactory, SoftDeletes;

        protected $table = 'kids_reviews';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'reviewable_id',
            'reviewable_type',
            'rating', // 1.0 - 5.0
            'comment',
            'safety_rating', // 1.0 - 5.0
            'is_verified_purchase',
            'media_urls', // JSON: photos, videos
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'rating' => 'float',
            'safety_rating' => 'float',
            'is_verified_purchase' => 'boolean',
            'media_urls' => 'json',
            'tags' => 'json',
        ];

        /**
         * Boot the model with tenant and correlation scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (string) (tenant()->id ?? 'system');
                $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Polymorphic relation (Can review Product, Center, Event).
         */
        public function reviewable(): MorphTo
        {
            return $this->morphTo();
        }

        /**
         * Filter by verified buyers.
         */
        public function scopeVerified(Builder $query): Builder
        {
            return $query->where('is_verified_purchase', true);
        }

        /**
         * Safety focus filter.
         */
        public function scopeHighSafety(Builder $query): Builder
        {
            return $query->where('safety_rating', '>=', 4.5);
        }

        /**
         * Visual content filter.
         */
        public function scopeWithMedia(Builder $query): Builder
        {
            return $query->whereNotNull('media_urls')->whereRaw("JSON_ARRAY_LENGTH(media_urls) > 0");
        }

        /**
         * Check if review is negative.
         */
        public function isCritical(): bool
        {
            return $this->rating <= 2.0;
        }

        /**
         * Get review text snippet.
         */
        public function getSnippetAttribute(): string
        {
            return Str::limit($this->comment, 100);
        }
}
