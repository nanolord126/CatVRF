<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerReview extends Model
{
    use HasFactory;

    use HasFactory, SoftDeletes;

        protected $table = 'flower_reviews';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'order_id',
            'shop_id',
            'user_id',
            'quality_rating',
            'delivery_rating',
            'freshness_rating',
            'overall_rating',
            'comment',
            'photos',
            'status',
            'helpful_count',
            'unhelpful_count',
            'verified_purchase',
            'correlation_id',
        ];

        protected $casts = [
            'photos' => 'json',
            'verified_purchase' => 'boolean',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function order(): BelongsTo
        {
            return $this->belongsTo(FlowerOrder::class);
        }

        public function shop(): BelongsTo
        {
            return $this->belongsTo(FlowerShop::class);
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }
}
