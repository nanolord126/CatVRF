<?php declare(strict_types=1);

namespace App\Models\Cleaning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class CleaningReview extends Model
{
    use HasFactory;

    protected $table = 'cleaning_reviews';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'cleaning_order_id',
            'rating_purity', // 1-5
            'rating_punctuality', // 1-5
            'rating_politeness', // 1-5
            'comment',
            'review_photos',
            'is_public',
            'correlation_id',
        ];

        protected $casts = [
            'rating_purity' => 'integer',
            'rating_punctuality' => 'integer',
            'rating_politeness' => 'integer',
            'is_public' => 'boolean',
            'review_photos' => 'json',
            'tenant_id' => 'integer',
            'user_id' => 'integer',
            'cleaning_order_id' => 'integer',
        ];

        /**
         * Boot logic for metadata and tenant isolation.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (int) (tenant()->id ?? 0);
            });

            static::addGlobalScope('tenant', function ($query) {
                if (tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * User/Customer author of the review.
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        /**
         * Associated order for this review.
         */
        public function order(): BelongsTo
        {
            return $this->belongsTo(CleaningOrder::class, 'cleaning_order_id');
        }

        /**
         * Average rating across all metrics.
         */
        public function getAverageRating(): float
        {
            return round(($this->rating_purity + $this->rating_punctuality + $this->rating_politeness) / 3, 1);
        }

        /**
         * Safety check for photo evidence.
         */
        public function hasEvidence(): bool
        {
            return !empty($this->review_photos);
        }
}
