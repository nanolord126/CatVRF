<?php declare(strict_types=1);

namespace App\Models\Art;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class ArtReview extends Model
{
    use HasFactory;

    protected $table = 'art_reviews';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'reviewable_id',
            'reviewable_type',
            'user_id',
            'rating',
            'comment',
            'media_json',
            'is_verified_purchase',
            'correlation_id',
        ];

        protected $casts = [
            'media_json' => 'array',
            'is_verified_purchase' => 'boolean',
            'rating' => 'integer',
        ];

        protected static function booted(): void
        {
            static::creating(function (ArtReview $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
            });

            static::addGlobalScope('tenant_id', function ($builder) {
                $builder->where('tenant_id', (tenant()->id ?? 1));
            });
        }

        /**
         * Parent entity being reviewed (Artwork, Artist, Gallery).
         */
        public function reviewable(): MorphTo
        {
            return $this->morphTo();
        }

        /**
         * Associated customer.
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }
}
