<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

final class FashionReview extends Model
{
    use TenantScoped;

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

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionProduct::class, 'product_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FashionOrder::class, 'order_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant_id', function ($query) {
            if (tenant()->id) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
