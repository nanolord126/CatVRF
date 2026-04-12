<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionReview extends Model
{
    use HasFactory;

    use SoftDeletes;

        protected $table = 'fashion_reviews';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'product_id',
            'reviewer_id',
            'order_id',
            'rating',
            'comment',
            'images',
            'review_aspects',
            'verified_purchase',
            'helpful_count',
            'unhelpful_count',
            'status',
            'correlation_id',
        ];

        protected $casts = [
            'images' => 'collection',
            'review_aspects' => 'collection',
            'verified_purchase' => 'boolean',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function ($query) {
                if (tenant()->id) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function product(): BelongsTo
        {
            return $this->belongsTo(FashionProduct::class, 'product_id');
        }

        public function reviewer(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'reviewer_id');
        }

        public function order(): BelongsTo
        {
            return $this->belongsTo(FashionOrder::class, 'order_id');
        }
}
