<?php

declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

final class FashionRetailReview extends Model
{
    protected $table = 'fashion_retail_reviews';

    protected $fillable = [
        'uuid',
        'product_id',
        'user_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'images',
        'helpful_count',
        'status',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'rating' => 'integer',
        'images' => 'json',
        'tags' => 'json',
        'helpful_count' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionRetailProduct::class, 'product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FashionRetailOrder::class, 'order_id');
    }

    protected static function booted(): void
    {
        parent::booted();
        self::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
